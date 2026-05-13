@extends('layouts.app')

@section('title', 'Admin — Агенции')

@section('content')
    <div class="mx-auto max-w-7xl px-4 py-8">
        <h1 class="text-2xl font-bold text-slate-900 mb-2">Агенции</h1>
        @include('admin._nav', ['active' => 'agencies'])

        <div class="mb-4 flex items-center justify-between gap-3 flex-wrap">
            <form method="GET" class="flex items-center gap-2">
                <input type="search" name="q" value="{{ request('q') }}"
                    placeholder="Пребарај по име, email, slug…"
                    class="rounded-md border-slate-300 text-sm w-72">
                <button type="submit" class="btn-secondary text-sm">Барај</button>
            </form>
            <a href="{{ route('admin.agencies.create') }}" class="btn-primary">+ Нова агенција</a>
        </div>

        <div class="rounded-lg border border-slate-200 bg-white shadow-sm overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-3 py-2">Агенција</th>
                        <th class="px-3 py-2">Email</th>
                        <th class="px-3 py-2">Tier</th>
                        <th class="px-3 py-2">До</th>
                        <th class="px-3 py-2 text-right">Огласи</th>
                        <th class="px-3 py-2">Статус</th>
                        <th class="px-3 py-2">Акции</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($agencies as $u)
                        <tr class="border-t border-slate-100 align-top {{ $u->isSuspended() ? 'bg-red-50' : '' }}">
                            <td class="px-3 py-2">
                                @if ($u->slug)
                                    <a href="{{ route('agency.show', $u) }}" class="font-medium text-sky-700 hover:underline">{{ $u->brand_name }}</a>
                                @else
                                    <span class="font-medium">{{ $u->brand_name }}</span>
                                @endif
                                @if ($u->role === 'admin')
                                    <span class="ml-1 inline-flex items-center rounded-full bg-purple-100 px-2 py-0.5 text-xs font-semibold text-purple-800">admin</span>
                                @endif
                                @if ($u->slug)
                                    <div class="text-xs text-slate-500">/{{ $u->slug }}</div>
                                @endif
                            </td>
                            <td class="px-3 py-2 text-slate-600">{{ $u->email }}</td>
                            <td class="px-3 py-2">
                                <form method="POST" action="{{ route('admin.users.update-tier', $u) }}" class="flex flex-col gap-1">
                                    @csrf
                                    @method('PATCH')
                                    <select name="subscription_tier" class="rounded border-slate-300 text-xs">
                                        @foreach ($tiers as $t)
                                            <option value="{{ $t->key }}" {{ $u->subscription_tier === $t->key ? 'selected' : '' }}>
                                                {{ $t->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <input type="date" name="subscription_until"
                                        value="{{ $u->subscription_until?->format('Y-m-d') }}"
                                        class="rounded border-slate-300 text-xs">
                                    <button type="submit" class="rounded bg-sky-600 px-2 py-1 text-xs font-semibold text-white hover:bg-sky-700">Зачувај</button>
                                </form>
                            </td>
                            <td class="px-3 py-2 text-slate-500 text-xs">{{ $u->subscription_until?->format('d.m.Y') ?? '—' }}</td>
                            <td class="px-3 py-2 text-right">{{ $u->listings_count }}</td>
                            <td class="px-3 py-2">
                                @if ($u->isSuspended())
                                    <span class="inline-flex items-center rounded-full bg-red-100 px-2 py-0.5 text-xs font-semibold text-red-800">Суспендиран</span>
                                    @if ($u->suspension_reason)
                                        <div class="text-xs text-red-700 mt-1">{{ $u->suspension_reason }}</div>
                                    @endif
                                @else
                                    <span class="inline-flex items-center rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-800">Активна</span>
                                @endif
                            </td>
                            <td class="px-3 py-2">
                                <div class="flex flex-col gap-1">
                                    @if ($u->isSuspended())
                                        <form method="POST" action="{{ route('admin.users.unsuspend', $u) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="rounded border border-emerald-300 px-2 py-1 text-xs font-medium text-emerald-700 hover:bg-emerald-50">
                                                Активирај
                                            </button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route('admin.users.suspend', $u) }}"
                                            onsubmit="const r = prompt('Причина за суспендирање (опционално):'); if (r === null) return false; this.reason.value = r;">
                                            @csrf
                                            <input type="hidden" name="reason" value="">
                                            <button type="submit" class="rounded border border-amber-300 px-2 py-1 text-xs font-medium text-amber-700 hover:bg-amber-50">
                                                Суспендирај
                                            </button>
                                        </form>
                                    @endif
                                    <form method="POST" action="{{ route('admin.users.destroy', $u) }}"
                                        onsubmit="return confirm('Сигурно избриши {{ $u->name }}? Ова е перманентно и брише и нивните огласи.')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rounded border border-red-300 px-2 py-1 text-xs font-medium text-red-700 hover:bg-red-50">
                                            Избриши
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $agencies->links() }}</div>
    </div>
@endsection
