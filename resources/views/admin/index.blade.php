@extends('layouts.admin', ['active' => 'index'])

@section('title', 'Преглед — admin')

@section('content')
    @php
        $cards = [
            ['label' => 'Агенции', 'value' => $stats['agencies'], 'tone' => 'sky'],
            ['label' => 'Корисници', 'value' => $stats['customers'], 'tone' => 'emerald'],
            ['label' => 'Платени планови', 'value' => $stats['paid'], 'tone' => 'violet'],
            ['label' => 'Огласи', 'value' => $stats['listings_total'], 'tone' => 'slate'],
            ['label' => 'Активни огласи', 'value' => $stats['listings_active'], 'tone' => 'emerald'],
            ['label' => 'Featured', 'value' => $stats['listings_featured'], 'tone' => 'amber'],
            ['label' => 'Прашања (30д)', 'value' => $stats['inquiries_30d'], 'tone' => 'sky'],
            ['label' => 'Сусп. корисници', 'value' => $stats['suspended'], 'tone' => 'red'],
            ['label' => 'Сусп. огласи', 'value' => $stats['listings_suspended'], 'tone' => 'red'],
            ['label' => 'Admin корисници', 'value' => $stats['admins'], 'tone' => 'purple'],
        ];

        $toneClasses = [
            'sky'     => 'bg-sky-50 text-sky-700 ring-sky-200',
            'emerald' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
            'violet'  => 'bg-violet-50 text-violet-700 ring-violet-200',
            'slate'   => 'bg-slate-50 text-slate-700 ring-slate-200',
            'amber'   => 'bg-amber-50 text-amber-700 ring-amber-200',
            'red'     => 'bg-red-50 text-red-700 ring-red-200',
            'purple'  => 'bg-purple-50 text-purple-700 ring-purple-200',
        ];
    @endphp

    <h1 class="text-2xl font-semibold text-slate-900 mb-6">Преглед</h1>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 mb-8">
        @foreach ($cards as $c)
            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ $c['label'] }}</div>
                <div class="mt-2 text-3xl font-bold text-slate-900 tabular-nums">{{ number_format($c['value']) }}</div>
                <div class="mt-2 inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset {{ $toneClasses[$c['tone']] }}">
                    {{ ucfirst($c['tone']) }}
                </div>
            </div>
        @endforeach
    </div>

    <div class="grid gap-4 md:grid-cols-3">
        <a href="{{ route('admin.agencies') }}" class="rounded-xl border border-slate-200 bg-white p-5 hover:border-sky-300 hover:shadow-md transition shadow-sm">
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-lg bg-sky-100 text-sky-700 flex items-center justify-center">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6h1.5m-1.5 3h1.5m-1.5 3h1.5"/></svg>
                </div>
                <div>
                    <h3 class="font-semibold text-slate-900">Агенции</h3>
                    <p class="text-sm text-slate-600">Управувај tier, суспенд, бришење</p>
                </div>
            </div>
        </a>
        <a href="{{ route('admin.listings') }}" class="rounded-xl border border-slate-200 bg-white p-5 hover:border-sky-300 hover:shadow-md transition shadow-sm">
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5"/></svg>
                </div>
                <div>
                    <h3 class="font-semibold text-slate-900">Огласи</h3>
                    <p class="text-sm text-slate-600">Boost, суспенд, бришење</p>
                </div>
            </div>
        </a>
        <a href="{{ route('admin.tiers') }}" class="rounded-xl border border-slate-200 bg-white p-5 hover:border-sky-300 hover:shadow-md transition shadow-sm">
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-lg bg-violet-100 text-violet-700 flex items-center justify-center">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09Z"/></svg>
                </div>
                <div>
                    <h3 class="font-semibold text-slate-900">Планови</h3>
                    <p class="text-sm text-slate-600">Tiers + toggle на функционалности</p>
                </div>
            </div>
        </a>
    </div>
@endsection
