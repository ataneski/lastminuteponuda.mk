@extends('layouts.admin', ['active' => 'tiers'])

@section('title', 'Планови — admin')

@section('actions')
    <a href="{{ route('admin.tiers.create') }}"
        class="inline-flex items-center gap-1.5 rounded-md bg-sky-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-sky-700">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
        Нов план
    </a>
@endsection

@section('content')
    <h1 class="text-2xl font-semibold text-slate-900 mb-2">Планови (tiers)</h1>
    <p class="text-sm text-slate-600 mb-6">
        Секој план е група од функционалности што се вклучени или исклучени по toggle.
        Промените важат веднаш за сите корисници на тој план.
    </p>

    @php
        $groupTones = [
            'limits' => 'bg-slate-100 text-slate-700',
            'marketing' => 'bg-amber-100 text-amber-800',
            'tools' => 'bg-sky-100 text-sky-800',
            'analytics' => 'bg-emerald-100 text-emerald-800',
            'branding' => 'bg-violet-100 text-violet-800',
            'support' => 'bg-rose-100 text-rose-800',
        ];
    @endphp

    <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
        @foreach ($tiers as $t)
            <article class="rounded-xl border {{ $t->active ? 'border-slate-200' : 'border-dashed border-slate-300 opacity-70' }} bg-white shadow-sm overflow-hidden flex flex-col">
                <header class="border-b border-slate-100 px-5 py-4 flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <h2 class="text-lg font-semibold text-slate-900">{{ $t->name }}</h2>
                            @if (! $t->active)
                                <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600">disabled</span>
                            @endif
                        </div>
                        <div class="text-xs text-slate-500 mt-0.5">
                            <code class="bg-slate-100 rounded px-1.5 py-0.5">{{ $t->key }}</code>
                            · {{ $t->users_count }} {{ $t->users_count === 1 ? 'корисник' : 'корисници' }}
                        </div>
                    </div>
                    <div class="text-right shrink-0">
                        <div class="text-2xl font-bold text-slate-900 tabular-nums">{{ number_format($t->monthly_price) }}</div>
                        <div class="text-xs text-slate-500">{{ $t->currency }} / мес.</div>
                    </div>
                </header>

                <div class="px-5 py-4 space-y-4 flex-1">
                    @foreach (\App\Models\Tier::GROUP_LABELS as $groupKey => $groupLabel)
                        @php $itemsInGroup = collect($featureMeta)->filter(fn ($m) => $m['group'] === $groupKey); @endphp
                        @if ($itemsInGroup->isEmpty()) @continue @endif
                        <div>
                            <div class="text-xs font-semibold uppercase tracking-wide {{ $groupTones[$groupKey] }} inline-block rounded px-2 py-0.5 mb-2">
                                {{ $groupLabel }}
                            </div>
                            <ul class="space-y-1 text-sm">
                                @foreach ($itemsInGroup as $key => $meta)
                                    @php $val = $t->feature($key); @endphp
                                    <li class="flex items-center justify-between gap-2">
                                        <span class="text-slate-700 truncate">{{ $meta['label'] }}</span>
                                        <span class="shrink-0">
                                            @if ($meta['type'] === 'bool')
                                                @if ($val)
                                                    <svg class="h-4 w-4 text-emerald-600 inline" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.857-9.809a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z" clip-rule="evenodd"/></svg>
                                                @else
                                                    <svg class="h-4 w-4 text-slate-300 inline" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 0 1 1.414 0L10 8.586l4.293-4.293a1 1 0 1 1 1.414 1.414L11.414 10l4.293 4.293a1 1 0 0 1-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 0 1-1.414-1.414L8.586 10 4.293 5.707a1 1 0 0 1 0-1.414Z" clip-rule="evenodd"/></svg>
                                                @endif
                                            @elseif ($meta['type'] === 'nullable_int' && $val === null)
                                                <span class="font-semibold text-emerald-700">∞</span>
                                            @else
                                                <span class="font-semibold text-slate-900 tabular-nums">{{ $val ?? '—' }}</span>
                                            @endif
                                        </span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                </div>

                <footer class="border-t border-slate-100 px-5 py-3 flex items-center justify-between bg-slate-50/50">
                    <a href="{{ route('admin.tiers.edit', $t) }}" class="text-sm font-medium text-sky-700 hover:underline">Уреди →</a>
                    @if ($t->users_count === 0)
                        <form method="POST" action="{{ route('admin.tiers.destroy', $t) }}"
                            onsubmit="return confirm('Избриши план {{ $t->key }}?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-sm font-medium text-red-700 hover:underline">Избриши</button>
                        </form>
                    @else
                        <span class="text-xs text-slate-400">не може да се избрише</span>
                    @endif
                </footer>
            </article>
        @endforeach
    </div>
@endsection
