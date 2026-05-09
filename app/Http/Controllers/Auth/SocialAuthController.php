<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirect;

/**
 * Google + Facebook one-click login for customers. Agencies still
 * register through the standard email/password flow because they
 * need a brand profile and verification we don't auto-derive from
 * social providers.
 *
 * Flow:
 *   1. /auth/{provider}/redirect — bounce to provider
 *   2. /auth/{provider}/callback — receive token + user info
 *      a. provider+provider_id already on a user → log them in
 *      b. matching email exists → link the provider to that account
 *         (we trust the provider's email verification)
 *      c. otherwise → create a fresh customer with no phone
 *   3. After login, if the user has no phone, send them to
 *      /dopolni-profil for a one-screen completion.
 */
class SocialAuthController extends Controller
{
    private const SUPPORTED = ['google', 'facebook'];

    public function redirect(string $provider): SymfonyRedirect
    {
        abort_unless(in_array($provider, self::SUPPORTED, true), 404);

        return Socialite::driver($provider)->redirect();
    }

    public function callback(string $provider): RedirectResponse
    {
        abort_unless(in_array($provider, self::SUPPORTED, true), 404);

        try {
            $social = Socialite::driver($provider)->user();
        } catch (\Throwable $e) {
            Log::warning('OAuth callback failed', [
                'provider' => $provider,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('login')
                ->withErrors(['email' => 'Логирањето преку '.ucfirst($provider).' не успеа. Обиди се повторно.']);
        }

        $user = $this->resolveOrCreateUser($provider, $social);
        Auth::login($user, remember: true);

        // First-time OAuth users typically have no phone — block with a
        // one-page completion before letting them browse with full access.
        if (! $user->phone) {
            return redirect()->route('customer.complete-profile');
        }

        return redirect()->intended('/');
    }

    private function resolveOrCreateUser(string $provider, $social): User
    {
        // 1. Existing OAuth identity
        $byProvider = User::where('provider', $provider)
            ->where('provider_id', (string) $social->getId())
            ->first();
        if ($byProvider) {
            $byProvider->forceFill([
                'provider_avatar' => $social->getAvatar() ?: $byProvider->provider_avatar,
            ])->save();

            return $byProvider;
        }

        // 2. Email collision → link provider to that account.
        $email = $social->getEmail();
        if ($email && ($byEmail = User::where('email', $email)->first())) {
            $byEmail->forceFill([
                'provider' => $provider,
                'provider_id' => (string) $social->getId(),
                'provider_avatar' => $social->getAvatar(),
                'email_verified_at' => $byEmail->email_verified_at ?? now(),
            ])->save();

            return $byEmail;
        }

        // 3. Brand new customer.
        $name = $social->getName() ?: $social->getNickname() ?: 'Корисник';
        [$firstName, $lastName] = $this->splitName($name);

        $user = User::create([
            'role' => User::ROLE_CUSTOMER,
            'name' => $name,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email ?: $provider.'-'.$social->getId().'@noemail.local',
            'password' => null,
            'provider' => $provider,
            'provider_id' => (string) $social->getId(),
            'provider_avatar' => $social->getAvatar(),
            // Marketing consent is NOT auto-granted from OAuth — must be
            // explicit. The complete-profile step asks for it.
            'marketing_consent' => false,
        ]);

        // email_verified_at isn't in $fillable; forceFill bypasses
        // mass-assignment so we can stamp it now (we trust the
        // provider's email verification).
        if ($email) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        return $user;
    }

    /** @return array{0:string,1:string} */
    private function splitName(string $full): array
    {
        $full = trim($full);
        if ($full === '') {
            return ['Корисник', ''];
        }
        $parts = preg_split('/\s+/u', $full, 2);

        return [$parts[0] ?? $full, $parts[1] ?? ''];
    }
}
