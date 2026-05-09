<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsAppIntakeSession extends Model
{
    protected $table = 'whatsapp_intake_sessions';

    public const STATE_COLLECTING = 'collecting';

    public const STATE_FINALIZING = 'finalizing';

    public const STATE_DONE = 'done';

    /** Hard cap on photos per session — matches the wizard's max. */
    public const MAX_MEDIA = 15;

    /** Idle minutes after which we auto-finalize. */
    public const IDLE_MINUTES = 15;

    /** Stop-words that immediately finalize the session. */
    public const STOP_WORDS = ['ГОТОВО', 'GOTOVO', 'DONE'];

    protected $fillable = [
        'user_id', 'wa_phone_e164', 'state',
        'media_paths', 'listing_id', 'last_message_at',
    ];

    protected function casts(): array
    {
        return [
            'media_paths' => 'array',
            'last_message_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }

    public function appendMedia(string $publicDiskPath): void
    {
        $paths = $this->media_paths ?? [];
        $paths[] = $publicDiskPath;
        $this->media_paths = $paths;
        $this->last_message_at = now();
        $this->save();
    }

    public function isCollecting(): bool
    {
        return $this->state === self::STATE_COLLECTING;
    }

    public function shouldAutoFinalize(): bool
    {
        if ($this->state !== self::STATE_COLLECTING) {
            return false;
        }
        if (count($this->media_paths ?? []) >= self::MAX_MEDIA) {
            return true;
        }

        return $this->last_message_at->lt(now()->subMinutes(self::IDLE_MINUTES));
    }
}
