@extends('layouts.app')

@section('title', 'Аналитика — '.$listing->title)

@section('content')
    <div class="mx-auto max-w-5xl px-4 py-8">
        <a href="{{ route('listings.mine') }}" class="text-sm text-sky-700 hover:underline">← Мои огласи</a>

        <header class="mt-3 mb-6 flex items-end justify-between flex-wrap gap-3">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Аналитика на оглас</h1>
                <p class="mt-1 text-slate-700">
                    <a href="{{ route('listings.show', $listing) }}" class="text-sky-700 hover:underline">{{ $listing->title }}</a>
                </p>
            </div>
            <a href="{{ route('listings.edit', $listing) }}" class="btn-secondary">Уреди оглас</a>
        </header>

        @if (! auth()->user()->isPro())
            <div class="rounded-lg border-2 border-dashed border-amber-300 bg-amber-50 p-10 text-center">
                <h2 class="text-lg font-semibold text-amber-900">Детална аналитика е достапна само за Pro</h2>
                <p class="mt-2 text-amber-800">
                    Овој оглас има <span class="font-bold">{{ number_format($listing->views_count) }}</span> прегледи и
                    <span class="font-bold">{{ $listing->inquiries()->count() }}</span> прашања вкупно.
                </p>
                <p class="mt-1 text-amber-800">Со Pro добивате 30/90-дневен график, дневни прегледи и листа на прашања.</p>
                <a href="{{ route('upgrade') }}" class="btn-primary mt-4">Надгради на Pro</a>
            </div>
        @else
            <livewire:listing-analytics :listing="$listing" />
        @endif
    </div>
@endsection
