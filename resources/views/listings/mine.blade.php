@extends('layouts.app')

@section('title', 'Мои огласи — lastminuteponuda.mk')

@section('content')
    <div class="mx-auto max-w-6xl px-4 py-8">
        <header class="mb-6 flex items-end justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Мои огласи</h1>
                <p class="text-slate-600">{{ $listings->total() }} огласи поставени од вас.</p>
            </div>
            <a href="{{ route('listings.create') }}" class="btn-primary">+ Нов оглас</a>
        </header>

        @if ($listings->isEmpty())
            <div class="rounded-lg border-2 border-dashed border-slate-300 bg-white p-10 text-center">
                <h3 class="text-lg font-semibold text-slate-800">Сè уште немате објавени огласи</h3>
                <p class="mt-1 text-slate-600">Поставете ја првата last minute понуда.</p>
                <a href="{{ route('listings.create') }}" class="btn-primary mt-4">Поставете оглас</a>
            </div>
        @else
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($listings as $listing)
                    <div class="flex flex-col gap-2 relative">
                        @if ($listing->isExpired())
                            <div class="absolute top-2 right-2 z-10 rounded-full bg-amber-500 px-2.5 py-0.5 text-xs font-semibold text-white shadow">
                                Истечен
                            </div>
                        @endif
                        <x-listing-card :listing="$listing" />

                        <div class="flex items-center justify-between text-xs text-slate-600 px-1">
                            <span title="Прегледи">👁 {{ number_format($listing->views_count) }} прегледи</span>
                            <span title="Прашања">✉ {{ $listing->inquiries_count }} прашања</span>
                        </div>

                        <div class="flex gap-2">
                            <a href="{{ route('listings.analytics', $listing) }}"
                                class="flex-1 inline-flex items-center justify-center rounded-md border border-slate-300 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-100">
                                Аналитика
                            </a>
                            <a href="{{ route('listings.edit', $listing) }}"
                                class="flex-1 inline-flex items-center justify-center rounded-md border border-slate-300 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-100">
                                Уреди
                            </a>
                            <form method="POST" action="{{ route('listings.destroy', $listing) }}"
                                onsubmit="return confirm('Сигурно сакате да го избришете овој оглас?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                    class="inline-flex items-center justify-center rounded-md border border-red-300 bg-white px-2.5 py-1.5 text-sm font-medium text-red-700 hover:bg-red-50"
                                    title="Избриши">
                                    🗑
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-6">{{ $listings->links() }}</div>
        @endif
    </div>
@endsection
