<?php

namespace App\Http\Controllers;

use App\Models\Listing;
use App\Models\ListingView as ListingViewRow;
use App\Services\SocialCardGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\View\View;

class ListingController extends Controller
{
    public function home(): View
    {
        $latest = Listing::query()
            ->active()
            ->orderByDesc('created_at')
            ->limit(6)
            ->get();

        return view('home', compact('latest'));
    }

    public function index(): View
    {
        return view('listings.index');
    }

    public function create(): View
    {
        return view('listings.create');
    }

    public function createAi(): \Illuminate\Contracts\View\View|\Illuminate\Http\RedirectResponse
    {
        if (! auth()->user()->feature('ai_wizard')) {
            return redirect()->route('upgrade')
                ->with('status', 'AI wizard не е дел од твојот план. Прегледи планови.');
        }

        return view('listings.create-ai');
    }

    public function show(Listing $listing): View
    {
        $isOwner = auth()->check() && $listing->user_id === auth()->id();
        $isAdmin = auth()->check() && auth()->user()->isAdmin();
        $hidden = $listing->isExpired() || $listing->isDraft() || $listing->isSuspended();
        if ($hidden && ! $isOwner && ! $isAdmin) {
            abort(404);
        }

        // Don't count owner views or admin/bot views in the metric.
        // Drafts also don't accrue counts (the agency hasn't published yet).
        if ((! auth()->check() || auth()->id() !== $listing->user_id) && ! $listing->isDraft()) {
            $listing->incrementQuietly('views_count');

            $today = now()->toDateString();
            $row = ListingViewRow::firstOrCreate(
                ['listing_id' => $listing->id, 'day' => $today],
                ['count' => 0]
            );
            $row->increment('count');
        }

        return view('listings.show', compact('listing'));
    }

    public function mine(): View
    {
        $listings = auth()->user()
            ->listings()
            ->withCount('inquiries')
            ->orderByDesc('created_at')
            ->paginate(12);

        return view('listings.mine', compact('listings'));
    }

    public function analytics(Listing $listing): View
    {
        $this->authorize('update', $listing);

        return view('listings.analytics', compact('listing'));
    }

    public function edit(Listing $listing): View
    {
        $this->authorize('update', $listing);

        return view('listings.edit', compact('listing'));
    }

    public function destroy(Listing $listing): RedirectResponse
    {
        $this->authorize('delete', $listing);

        $listing->delete();

        return redirect()
            ->route('listings.mine')
            ->with('status', 'Огласот е избришан.');
    }

    public function sitemap(): Response
    {
        $listings = Listing::query()->active()->latest('updated_at')->get();

        return response()
            ->view('seo.sitemap', compact('listings'))
            ->header('Content-Type', 'application/xml');
    }

    public function socialCard(Listing $listing, SocialCardGenerator $gen): Response
    {
        if ($listing->isExpired() && (! auth()->check() || auth()->id() !== $listing->user_id)) {
            abort(404);
        }

        return response($gen->renderPng($listing), 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
