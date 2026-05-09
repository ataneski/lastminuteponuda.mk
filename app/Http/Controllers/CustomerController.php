<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function profile(): View
    {
        return view('customer.profile', [
            'user' => auth()->user(),
        ]);
    }

    /**
     * One-screen completion shown to OAuth-new users who have no phone
     * yet. Required so the marketing list stays usable.
     */
    public function completeProfile(): View|RedirectResponse
    {
        $user = auth()->user();
        if ($user->phone) {
            return redirect()->intended('/');
        }

        return view('customer.complete-profile', ['user' => $user]);
    }

    public function storeCompleteProfile(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'phone' => ['required', 'string', 'max:50'],
            'first_name' => ['required', 'string', 'max:60'],
            'last_name' => ['required', 'string', 'max:60'],
            'marketing_consent' => ['sometimes', 'boolean'],
        ]);

        $consent = (bool) ($data['marketing_consent'] ?? false);

        $user->forceFill([
            'phone' => $data['phone'],
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'name' => trim($data['first_name'].' '.$data['last_name']),
            'marketing_consent' => $consent,
            'marketing_consent_at' => $consent && ! $user->marketing_consent_at ? now() : $user->marketing_consent_at,
        ])->save();

        return redirect()->intended('/');
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:60'],
            'last_name' => ['required', 'string', 'max:60'],
            'phone' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'marketing_consent' => ['sometimes', 'boolean'],
        ]);

        $previousConsent = (bool) $user->marketing_consent;
        $newConsent = (bool) ($data['marketing_consent'] ?? false);

        $user->forceFill([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'name' => trim($data['first_name'].' '.$data['last_name']),
            'phone' => $data['phone'],
            'email' => $data['email'],
            'marketing_consent' => $newConsent,
            // Stamp the timestamp only when consent flips to true.
            'marketing_consent_at' => $newConsent && ! $previousConsent
                ? now()
                : $user->marketing_consent_at,
        ])->save();

        return back()->with('status', 'Профилот е ажуриран.');
    }
}
