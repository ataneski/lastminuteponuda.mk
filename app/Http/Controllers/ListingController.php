<?php

namespace App\Http\Controllers;

use App\Models\Listing;
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

    public function show(Listing $listing): View
    {
        if ($listing->isExpired() && (! auth()->check() || $listing->user_id !== auth()->id())) {
            abort(404);
        }

        return view('listings.show', compact('listing'));
    }

    public function mine(): View
    {
        $listings = auth()->user()
            ->listings()
            ->orderByDesc('created_at')
            ->paginate(12);

        return view('listings.mine', compact('listings'));
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
}
