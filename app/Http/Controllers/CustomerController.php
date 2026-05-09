<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function profile(): View
    {
        return view('customer.profile', [
            'user' => auth()->user(),
        ]);
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
