<?php

namespace App\Http\Controllers;

use App\Jobs\FinalizeWhatsAppIntakeJob;
use App\Models\User;
use App\Models\WhatsAppIntakeSession;
use App\Services\ImageProcessor;
use App\Services\WhatsAppClient;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class WhatsAppWebhookController extends Controller
{
    public function __construct(
        private readonly WhatsAppClient $whatsapp,
        private readonly ImageProcessor $processor,
    ) {}

    /**
     * GET handler — Meta verification challenge.
     */
    public function verify(Request $request): Response
    {
        $mode = $request->query('hub_mode');
        $token = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        if ($mode === 'subscribe' && $token === config('services.whatsapp.verify_token')) {
            return response((string) $challenge, 200);
        }

        return response('forbidden', 403);
    }

    /**
     * POST handler — incoming messages. Always returns 200 (even on
     * spoofed signatures) so Meta does not retry indefinitely.
     */
    public function handle(Request $request): Response
    {
        if (! $this->signatureValid($request)) {
            Log::warning('WhatsApp webhook: invalid signature', [
                'header' => $request->header('X-Hub-Signature-256'),
            ]);

            return response('ok', 200);
        }

        $payload = $request->json()->all();

        foreach ($payload['entry'] ?? [] as $entry) {
            foreach ($entry['changes'] ?? [] as $change) {
                foreach ($change['value']['messages'] ?? [] as $message) {
                    $this->handleMessage($message);
                }
            }
        }

        return response('ok', 200);
    }

    private function signatureValid(Request $request): bool
    {
        $secret = config('services.whatsapp.app_secret');
        if (! $secret) {
            // Dev/local: skip verification when no secret configured.
            return app()->environment('local', 'testing');
        }
        $header = $request->header('X-Hub-Signature-256');
        if (! $header) {
            return false;
        }
        $expected = 'sha256='.hash_hmac('sha256', $request->getContent(), $secret);

        return hash_equals($expected, $header);
    }

    private function handleMessage(array $message): void
    {
        $from = $message['from'] ?? null;
        $text = trim($message['text']['body'] ?? '');
        if (! $from) {
            return;
        }
        $e164 = '+'.ltrim($from, '+');

        $user = User::where('wa_phone_e164', $e164)->first();

        // PAIRING: not yet paired → expect a pairing code as text.
        if (! $user) {
            if ($text === '') {
                return; // ignore media before pairing
            }
            $code = strtoupper(preg_replace('/\s+/', '', $text));
            $candidate = User::where('wa_pairing_code', $code)->first();
            if ($candidate) {
                $candidate->forceFill([
                    'wa_phone_e164' => $e164,
                    'wa_paired_at' => now(),
                    'wa_pairing_code' => null,
                ])->save();

                $this->whatsapp->sendText(
                    $e164,
                    "Здраво {$candidate->brand_name}! Парирани сте. Сега може да ".
                    'праќате слики за нов оглас. Кога завршите, напишете ГОТОВО.'
                );
            }

            return;
        }

        // MESSAGE: stop-word finalizes immediately.
        if ($text !== '' && in_array(mb_strtoupper($text), WhatsAppIntakeSession::STOP_WORDS, true)) {
            $this->finalizeFor($user);

            return;
        }

        // MEDIA: download & store synchronously (Meta URLs expire in 5min).
        $mediaId = $message['image']['id'] ?? $message['document']['id'] ?? null;
        if ($mediaId) {
            $bytes = $this->whatsapp->downloadMediaBytes($mediaId);
            if (! $bytes) {
                Log::warning('WhatsApp media download failed', ['media_id' => $mediaId]);

                return;
            }

            $stored = $this->processor->processBytes($bytes);

            $session = WhatsAppIntakeSession::firstOrNew(
                ['user_id' => $user->id, 'state' => WhatsAppIntakeSession::STATE_COLLECTING]
            );
            if (! $session->exists) {
                $session->wa_phone_e164 = $e164;
                $session->media_paths = [];
                $session->last_message_at = now();
                $session->save();
            }
            $session->appendMedia($stored['path']);

            // Hard-cap auto-finalize.
            if ($session->shouldAutoFinalize()) {
                $this->finalizeFor($user);
            }

            return;
        }
    }

    private function finalizeFor(User $user): void
    {
        $session = WhatsAppIntakeSession::where('user_id', $user->id)
            ->where('state', WhatsAppIntakeSession::STATE_COLLECTING)
            ->latest()
            ->first();

        if (! $session || empty($session->media_paths)) {
            return;
        }

        $session->update(['state' => WhatsAppIntakeSession::STATE_FINALIZING]);
        FinalizeWhatsAppIntakeJob::dispatch($session->id);
    }
}
