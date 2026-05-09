<?php

namespace App\Http\Controllers;

use App\Models\Listing;
use Illuminate\View\View;

class ListingController extends Controller
{
    public function home(): View
    {
        $latest = Listing::query()
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
}
