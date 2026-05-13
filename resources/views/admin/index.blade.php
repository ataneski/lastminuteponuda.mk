@extends('layouts.app')

@section('title', 'Admin — lastminuteponuda.mk')

@section('content')
    <div class="mx-auto max-w-7xl px-4 py-8">
        <h1 class="text-2xl font-bold text-slate-900 mb-2">Admin преглед</h1>
        @include('admin._nav', ['active' => 'index'])

        <div class="grid gap-3 md:grid-cols-5 mb-6">
            @foreach ([
                'Агенции' => $stats['agencies'],
                'Корисници' => $stats['customers'],
                'Платени' => $stats['paid'],
                'Огласи' => $stats['listings_total'],
                'Активни огласи' => $stats['listings_active'],
                'Featured' => $stats['listings_featured'],
                'Прашања (30д)' => $stats['inquiries_30d'],
                'Суспендирани корисници' => $stats['suspended'],
                'Суспендирани огласи' => $stats['listings_suspended'],
                'Admin корисници' => $stats['admins'],
            ] as $label => $value)
                <div class="rounded-lg border border-slate-200 bg-white p-3 shadow-sm">
                    <div class="text-xs uppercase text-slate-500">{{ $label }}</div>
                    <div class="mt-1 text-xl font-bold text-slate-900">{{ number_format($value) }}</div>
                </div>
            @endforeach
        </div>

        <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-sm text-slate-600">
                Користи ги табовите горе за управување. Тоа е центарот за <strong>Агенции</strong>
                (управување, суспенд, бришење), <strong>Огласи</strong> (boost, суспенд, бришење) и
                <strong>Tiers</strong> (планови со toggle на функционалности).
            </p>
        </div>
    </div>
@endsection
