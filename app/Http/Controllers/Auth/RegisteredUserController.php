<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Default registration is the customer/visitor flow — the marketing
     * top of the funnel. Agencies have a separate route via {@see createAgency()}.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:60'],
            'last_name' => ['required', 'string', 'max:60'],
            'phone' => ['required', 'string', 'max:50'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'marketing_consent' => ['sometimes', 'boolean'],
        ]);

        $user = User::create([
            'role' => User::ROLE_CUSTOMER,
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'name' => trim($data['first_name'].' '.$data['last_name']),
            'phone' => $data['phone'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'marketing_consent' => (bool) ($data['marketing_consent'] ?? false),
            'marketing_consent_at' => ($data['marketing_consent'] ?? false) ? now() : null,
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect()->intended('/');
    }

    /**
     * Agency registration view — same controller, separate flow.
     */
    public function createAgency(): View
    {
        return view('auth.register-agencija');
    }

    /**
     * @throws ValidationException
     */
    public function storeAgency(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'role' => User::ROLE_AGENCY,
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect()->route('agency.profile.edit');
    }
}
