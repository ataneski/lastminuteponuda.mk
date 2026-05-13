@extends('layouts.admin', ['active' => 'listings'])

@section('title', 'Огласи — admin')

@section('content')
    <div class="flex items-center justify-between mb-6 gap-3 flex-wrap">
        <h1 class="text-2xl font-semibold text-slate-900">Огласи</h1>
        <form method="GET" class="flex items-center gap-2">
            <div class="relative">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.2-5.2m2.2-5.3a7.5 7.5 0 1 1-15 0 7.5 7.5 0 0 1 15 0Z"/></svg>
                <input type="search" name="q" value="{{ request('q') }}"
                    placeholder="Наслов, дестинација, хотел…"
                    class="rounded-md border-slate-300 text-sm pl-9 pr-3 py-1.5 w-80">
            </div>
            <button type="submit" class="rounded-md border border-slate-300 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50">Барај</button>
        </form>
    </div>

        <div class="rounded-lg border border-slate-200 bg-white shadow-sm overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-3 py-2">#</th>
                        <th class="px-3 py-2">Наслов</th>
                        <th class="px-3 py-2">Агенција</th>
                        <th class="px-3 py-2 text-right">Прегл.</th>
                        <th class="px-3 py-2 text-right">Праш.</th>
                        <th class="px-3 py-2">Истекува</th>
                        <th class="px-3 py-2">Featured</th>
                        <th class="px-3 py-2">Статус</th>
                        <th class="px-3 py-2">Акции</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($listings as $l)
                        <tr class="border-t border-slate-100 align-top
                            {{ $l->isSuspended() ? 'bg-red-50' : '' }}">
                            <td class="px-3 py-2 text-slate-500">{{ $l->id }}</td>
                            <td class="px-3 py-2">
                                <a href="{{ route('listings.show', $l) }}" class="font-medium text-sky-700 hover:underline">
                                    {{ \Illuminate\Support\Str::limit($l->title, 50) }}
                                </a>
                                @if ($l->isDraft())
                                    <span class="ml-1 inline-flex items-center rounded-full bg-slate-200 px-2 py-0.5 text-xs">draft</span>
                                @endif
                            </td>
                            <td class="px-3 py-2 text-slate-600">{{ $l->user?->brand_name ?? $l->agency_name }}</td>
                            <td class="px-3 py-2 text-right">{{ number_format($l->views_count) }}</td>
                            <td class="px-3 py-2 text-right">{{ $l->inquiries_count }}</td>
                            <td class="px-3 py-2 text-slate-500 text-xs">{{ $l->expires_at?->format('d.m.Y') ?? '—' }}</td>
                            <td class="px-3 py-2 text-xs">
                                @if ($l->isFeatured())
                                    <span class="inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 font-semibold text-amber-800">
                                        до {{ $l->featured_until->format('d.m.Y') }}
                                    </span>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="px-3 py-2">
                                @if ($l->isSuspended())
                                    <span class="inline-flex items-center rounded-full bg-red-100 px-2 py-0.5 text-xs font-semibold text-red-800">Суспендиран</span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-800">Активен</span>
                                @endif
                            </td>
                            <td class="px-3 py-2">
                                <div class="flex flex-col gap-1">
                                    <form method="POST" action="{{ route('admin.listings.feature', $l) }}" class="flex items-center gap-1">
                                        @csrf
                                        <input type="number" name="days" value="30" min="1" max="365" class="w-14 rounded border-slate-300 text-xs">
                                        <button type="submit" class="rounded bg-amber-500 px-2 py-1 text-xs font-semibold text-white hover:bg-amber-600">Boost</button>
                                    </form>
                                    @if ($l->isFeatured())
                                        <form method="POST" action="{{ route('admin.listings.unfeature', $l) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="rounded border border-slate-300 px-2 py-1 text-xs font-medium text-slate-600 hover:bg-slate-100">Un-boost</button>
                                        </form>
                                    @endif
                                    @if ($l->isSuspended())
                                        <form method="POST" action="{{ route('admin.listings.unsuspend', $l) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="rounded border border-emerald-300 px-2 py-1 text-xs font-medium text-emerald-700 hover:bg-emerald-50">Активирај</button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route('admin.listings.suspend', $l) }}"
                                            onsubmit="const r = prompt('Причина (опционално):'); if (r === null) return false; this.reason.value = r;">
                                            @csrf
                                            <input type="hidden" name="reason" value="">
                                            <button type="submit" class="rounded border border-amber-300 px-2 py-1 text-xs font-medium text-amber-700 hover:bg-amber-50">Суспендирај</button>
                                        </form>
                                    @endif
                                    <form method="POST" action="{{ route('admin.listings.destroy', $l) }}"
                                        onsubmit="return confirm('Сигурно избриши #{{ $l->id }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rounded border border-red-300 px-2 py-1 text-xs font-medium text-red-700 hover:bg-red-50">Избриши</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $listings->links() }}</div>
@endsection
