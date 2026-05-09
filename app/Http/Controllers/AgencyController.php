<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\View\View;

class AgencyController extends Controller
{
    public function edit(): View
    {
        return view('agency.profile-edit');
    }

    public function show(User $agency): View
    {
        $listings = $agency->listings()
            ->active()
            ->orderByDesc('created_at')
            ->paginate(12);

        return view('agency.show', [
            'agency' => $agency,
            'listings' => $listings,
        ]);
    }

    public function analytics(): View
    {
        return view('agency.analytics');
    }
}
