@extends('layouts.app')

@section('title', 'Admin — lastminuteponuda.mk')

@section('content')
    <div class="mx-auto max-w-7xl px-4 py-8">
        <header class="mb-6 flex items-end justify-between flex-wrap gap-3">
            <h1 class="text-2xl font-bold text-slate-900">Admin</h1>
            <nav class="flex gap-3 text-sm">
                <a href="{{ route('admin.index') }}" class="font-medium text-sky-700">Агенции</a>
                <a href="{{ route('admin.listings') }}" class="text-slate-600 hover:text-slate-900">Огласи</a>
            </nav>
        </header>

        <div class="grid gap-3 md:grid-cols-6 mb-6">
            @foreach ([
                'Агенции' => $stats['agencies'],
                'Платени' => $stats['paid'],
                'Огласи' => $stats['listings_total'],
                'Активни' => $stats['listings_active'],
                'Featured' => $stats['listings_featured'],
                'Прашања 30д' => $stats['inquiries_30d'],
            ] as $label => $value)
                <div class="rounded-lg border border-slate-200 bg-white p-3 shadow-sm">
                    <div class="text-xs uppercase text-slate-500">{{ $label }}</div>
                    <div class="mt-1 text-xl font-bold text-slate-900">{{ number_format($value) }}</div>
                </div>
            @endforeach
        </div>

        <div class="rounded-lg border border-slate-200 bg-white shadow-sm overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-3 py-2">Агенција</th>
                        <th class="px-3 py-2">Email</th>
                        <th class="px-3 py-2">Tier</th>
                        <th class="px-3 py-2">До</th>
                        <th class="px-3 py-2 text-right">Огласи</th>
                        <th class="px-3 py-2 text-right">Прашања</th>
                        <th class="px-3 py-2">Acted</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($users as $u)
                        <tr class="border-t border-slate-100 align-top">
                            <td class="px-3 py-2">
                                <a href="{{ route('agency.show', $u) }}" class="font-medium text-sky-700 hover:underline">{{ $u->brand_name }}</a>
                                @if ($u->is_admin)
                                    <span class="ml-1 inline-flex items-center rounded-full bg-purple-100 px-2 py-0.5 text-xs font-semibold text-purple-800">admin</span>
                                @endif
                                <div class="text-xs text-slate-500">/{{ $u->slug }}</div>
                            </td>
                            <td class="px-3 py-2 text-slate-600">{{ $u->email }}</td>
                            <td class="px-3 py-2">{{ \App\Models\User::TIER_LABELS[$u->effectiveTier()] ?? '?' }}</td>
                            <td class="px-3 py-2 text-slate-500">{{ $u->subscription_until?->format('d.m.Y') ?? '—' }}</td>
                            <td class="px-3 py-2 text-right">{{ $u->listings_count }}</td>
                            <td class="px-3 py-2 text-right">{{ $u->inquiries_count }}</td>
                            <td class="px-3 py-2">
                                <form method="POST" action="{{ route('admin.users.update-tier', $u) }}" class="flex items-center gap-1">
                                    @csrf
                                    @method('PATCH')
                                    <select name="subscription_tier" class="rounded border-slate-300 text-xs">
                                        @foreach (\App\Models\User::TIER_LABELS as $k => $label)
                                            <option value="{{ $k }}" {{ $u->subscription_tier === $k ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    <input type="date" name="subscription_until" value="{{ $u->subscription_until?->format('Y-m-d') }}" class="rounded border-slate-300 text-xs">
                                    <button type="submit" class="rounded bg-sky-600 px-2 py-1 text-xs font-semibold text-white hover:bg-sky-700">Зачувај</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $users->links() }}</div>
    </div>
@endsection
