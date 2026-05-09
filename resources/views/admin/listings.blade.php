@extends('layouts.app')

@section('title', 'Admin — Огласи')

@section('content')
    <div class="mx-auto max-w-7xl px-4 py-8">
        <header class="mb-6 flex items-end justify-between flex-wrap gap-3">
            <h1 class="text-2xl font-bold text-slate-900">Огласи</h1>
            <nav class="flex gap-3 text-sm">
                <a href="{{ route('admin.index') }}" class="text-slate-600 hover:text-slate-900">Агенции</a>
                <a href="{{ route('admin.listings') }}" class="font-medium text-sky-700">Огласи</a>
            </nav>
        </header>

        <div class="rounded-lg border border-slate-200 bg-white shadow-sm overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-3 py-2">#</th>
                        <th class="px-3 py-2">Наслов</th>
                        <th class="px-3 py-2">Агенција</th>
                        <th class="px-3 py-2 text-right">Прегледи</th>
                        <th class="px-3 py-2 text-right">Прашања</th>
                        <th class="px-3 py-2">Истекува</th>
                        <th class="px-3 py-2">Featured</th>
                        <th class="px-3 py-2">Boost</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($listings as $l)
                        <tr class="border-t border-slate-100 align-top">
                            <td class="px-3 py-2 text-slate-500">{{ $l->id }}</td>
                            <td class="px-3 py-2">
                                <a href="{{ route('listings.show', $l) }}" class="font-medium text-sky-700 hover:underline">
                                    {{ \Illuminate\Support\Str::limit($l->title, 50) }}
                                </a>
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
                                <div class="flex items-center gap-1">
                                    <form method="POST" action="{{ route('admin.listings.feature', $l) }}" class="flex items-center gap-1">
                                        @csrf
                                        <input type="number" name="days" value="30" min="1" max="365" class="w-16 rounded border-slate-300 text-xs">
                                        <button type="submit" class="rounded bg-amber-500 px-2 py-1 text-xs font-semibold text-white hover:bg-amber-600">Boost</button>
                                    </form>
                                    @if ($l->isFeatured())
                                        <form method="POST" action="{{ route('admin.listings.unfeature', $l) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="rounded border border-slate-300 px-2 py-1 text-xs font-medium text-slate-600 hover:bg-slate-100">Off</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $listings->links() }}</div>
    </div>
@endsection
