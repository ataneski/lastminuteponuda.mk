<?php

namespace App\Http\Controllers;

use App\Models\Listing;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function index(): View
    {
        $stats = [
            'agencies' => User::count(),
            'paid' => User::whereIn('subscription_tier', [User::TIER_PRO, User::TIER_PREMIUM])
                ->where(function ($q) {
                    $q->whereNull('subscription_until')->orWhere('subscription_until', '>', now());
                })
                ->count(),
            'listings_total' => Listing::count(),
            'listings_active' => Listing::active()->count(),
            'listings_featured' => Listing::featured()->count(),
            'inquiries_30d' => \App\Models\Inquiry::where('created_at', '>=', now()->subDays(30))->count(),
        ];

        $users = User::query()
            ->withCount(['listings', 'inquiries'])
            ->orderByDesc('id')
            ->paginate(20);

        return view('admin.index', compact('stats', 'users'));
    }

    public function updateUserTier(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'subscription_tier' => 'required|in:free,pro,premium',
            'subscription_until' => 'nullable|date',
            'is_admin' => 'sometimes|boolean',
        ]);

        $user->update([
            'subscription_tier' => $data['subscription_tier'],
            'subscription_until' => $data['subscription_until'] ?? null,
            'is_admin' => (bool) ($data['is_admin'] ?? $user->is_admin),
        ]);

        return back()->with('status', "Tier на {$user->name} ажуриран.");
    }

    public function listings(): View
    {
        $listings = Listing::query()
            ->with('user')
            ->withCount('inquiries')
            ->orderByDesc('id')
            ->paginate(30);

        return view('admin.listings', compact('listings'));
    }

    public function featureListing(Request $request, Listing $listing): RedirectResponse
    {
        $data = $request->validate([
            'days' => 'required|integer|min:1|max:365',
        ]);

        $listing->update([
            'featured_until' => now()->addDays((int) $data['days']),
        ]);

        return back()->with('status', "Огласот #{$listing->id} промовиран {$data['days']} дена.");
    }

    public function unfeatureListing(Listing $listing): RedirectResponse
    {
        $listing->update(['featured_until' => null]);

        return back()->with('status', "Огласот #{$listing->id} веќе не е промовиран.");
    }
}
