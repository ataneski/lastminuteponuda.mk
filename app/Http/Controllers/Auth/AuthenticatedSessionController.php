<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $user = $request->user();

        // Suspended users can authenticate (credentials valid) but never
        // hold a session. Logout and surface a clear validation error.
        if ($user?->isSuspended()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            throw ValidationException::withMessages([
                'email' => $user->suspension_reason
                    ? 'Сметката е суспендирана: '.$user->suspension_reason
                    : 'Сметката е суспендирана. Контактирај поддршка.',
            ]);
        }

        $request->session()->regenerate();

        // Default landing depends on the role:
        //   - admin    → admin panel
        //   - agency   → their listings dashboard
        //   - customer → home (where prices are now visible)
        // intended() preserves any URL the user was bounced from.
        $default = match (true) {
            $user?->isAdmin()    => route('admin.index'),
            $user?->isAgency()   => route('listings.mine'),
            default              => '/',
        };

        return redirect()->intended($default);
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
