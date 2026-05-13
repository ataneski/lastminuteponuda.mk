@extends('layouts.app')

@section('title', 'Admin — Tiers')

@section('content')
    <div class="mx-auto max-w-7xl px-4 py-8">
        <h1 class="text-2xl font-bold text-slate-900 mb-2">Tiers (планови)</h1>
        @include('admin._nav', ['active' => 'tiers'])

        <div class="mb-4 flex items-center justify-end">
            <a href="{{ route('admin.tiers.create') }}" class="btn-primary">+ Нов tier</a>
        </div>

        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
            @foreach ($tiers as $t)
                <div class="rounded-lg border {{ $t->active ? 'border-slate-200' : 'border-dashed border-slate-300 opacity-60' }} bg-white p-5 shadow-sm">
                    <div class="flex items-start justify-between">
                        <div>
                            <h2 class="text-lg font-semibold text-slate-900">{{ $t->name }}</h2>
                            <div class="text-xs text-slate-500">key: <code>{{ $t->key }}</code></div>
                        </div>
                        <div class="text-right">
                            <div class="text-xl font-bold text-slate-900">
                                {{ number_format($t->monthly_price) }} <span class="text-sm font-normal text-slate-500">{{ $t->currency }}/мес.</span>
                            </div>
                            <div class="text-xs text-slate-500">{{ $t->users_count }} корисници</div>
                        </div>
                    </div>

                    <ul class="mt-4 space-y-1.5 text-sm">
                        @foreach ($featureMeta as $key => $meta)
                            @php $val = $t->feature($key); @endphp
                            <li class="flex items-start justify-between gap-2">
                                <span class="text-slate-600">{{ $meta['label'] }}</span>
                                <span class="text-right">
                                    @if ($meta['type'] === 'bool')
                                        @if ($val)
                                            <span class="text-emerald-700 font-semibold">✓</span>
                                        @else
                                            <span class="text-slate-400">—</span>
                                        @endif
                                    @elseif ($meta['type'] === 'nullable_int' && $val === null)
                                        <span class="text-emerald-700 font-semibold">∞</span>
                                    @else
                                        <span class="font-semibold text-slate-900">{{ $val ?? '—' }}</span>
                                    @endif
                                </span>
                            </li>
                        @endforeach
                    </ul>

                    <div class="mt-5 flex items-center justify-between gap-2">
                        <a href="{{ route('admin.tiers.edit', $t) }}" class="text-sm font-medium text-sky-700 hover:underline">Уреди</a>
                        @if ($t->users_count === 0)
                            <form method="POST" action="{{ route('admin.tiers.destroy', $t) }}"
                                onsubmit="return confirm('Избриши tier {{ $t->key }}?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-sm font-medium text-red-700 hover:underline">Избриши</button>
                            </form>
                        @else
                            <span class="text-xs text-slate-500">не може да се избрише (има корисници)</span>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        @if ($errors->any())
            <div class="mt-4 rounded-md border border-red-300 bg-red-50 p-3 text-sm text-red-800">
                {{ $errors->first() }}
            </div>
        @endif
    </div>
@endsection
