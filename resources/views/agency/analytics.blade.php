@extends('layouts.app')

@section('title', 'Аналитика — lastminuteponuda.mk')

@section('content')
    <div class="mx-auto max-w-6xl px-4 py-8">
        <header class="mb-6 flex items-end justify-between flex-wrap gap-3">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Аналитика</h1>
                <p class="text-slate-600">Прегледи, прашања и конверзија за вашите огласи.</p>
            </div>
            <span class="inline-flex items-center gap-1 rounded-full bg-sky-100 px-3 py-1 text-xs font-semibold text-sky-800">
                {{ \App\Models\User::TIER_LABELS[auth()->user()->effectiveTier()] }}
            </span>
        </header>

        @if (! auth()->user()->feature('analytics_enabled'))
            <div class="rounded-lg border-2 border-dashed border-amber-300 bg-amber-50 p-10 text-center">
                <h2 class="text-lg font-semibold text-amber-900">Аналитиката не е дел од твојот план</h2>
                <p class="mt-2 text-amber-800">План со „Аналитика" feature дава KPI dashboard со прегледи, прашања и конверзија по оглас.</p>
                <a href="{{ route('upgrade') }}" class="btn-primary mt-4">Прегледи планови</a>
            </div>
        @else
            <livewire:agency-analytics />
        @endif
    </div>
@endsection
