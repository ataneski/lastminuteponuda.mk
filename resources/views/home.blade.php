@extends('layouts.app')

@section('content')
    <section class="bg-gradient-to-br from-sky-700 to-sky-500 text-white">
        <div class="mx-auto max-w-6xl px-4 py-14">
            <h1 class="text-3xl md:text-4xl font-bold leading-tight">
                Last minute понуди од македонските туристички агенции
            </h1>
            <p class="mt-3 text-sky-50 max-w-2xl">
                Сите актуелни попусти на едно место. Агенциите објавуваат брзо и лесно — за помалку од минута.
            </p>
            <div class="mt-6 flex flex-wrap gap-3">
                <a href="{{ route('listings.create') }}"
                    class="inline-flex items-center rounded-md bg-white text-sky-700 font-semibold px-4 py-2 hover:bg-sky-50">
                    + Поставете оглас
                </a>
                <a href="{{ route('listings.index') }}"
                    class="inline-flex items-center rounded-md border border-white/40 px-4 py-2 hover:bg-white/10">
                    Сите огласи
                </a>
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-6xl px-4 py-10">
        <div class="flex items-end justify-between mb-5">
            <h2 class="text-xl font-semibold text-slate-900">Најнови понуди</h2>
            <a href="{{ route('listings.index') }}" class="text-sm font-medium text-sky-700 hover:underline">
                Види ги сите →
            </a>
        </div>

        @if ($latest->isEmpty())
            <div class="rounded-lg border-2 border-dashed border-slate-300 bg-white p-10 text-center">
                <h3 class="text-lg font-semibold text-slate-800">Сè уште нема објавени огласи</h3>
                <p class="mt-1 text-slate-600">Бидете првата агенција која ќе постави last minute понуда.</p>
                <a href="{{ route('listings.create') }}" class="btn-primary mt-4">Поставете оглас</a>
            </div>
        @else
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($latest as $listing)
                    <x-listing-card :listing="$listing" />
                @endforeach
            </div>
        @endif
    </section>
@endsection
