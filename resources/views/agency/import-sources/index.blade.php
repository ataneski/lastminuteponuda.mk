@extends('layouts.app')

@section('title', 'Auto-import — lastminuteponuda.mk')

@section('content')
    <div class="mx-auto max-w-5xl px-4 py-8">
        <header class="mb-6 flex items-end justify-between flex-wrap gap-3">
            <div>
                <h1 class="text-2xl font-semibold text-slate-900">Auto-import од твојот сајт</h1>
                <p class="mt-1 text-sm text-slate-600">
                    Дај URL на твојата „Last Minute" страница. Нашиот AI ги извлекува понудите и ги создава како draft огласи кои ти ги прегледуваш и објавуваш.
                </p>
            </div>
            <a href="{{ route('agency.import-sources.create') }}"
                class="inline-flex items-center gap-1.5 rounded-md bg-sky-600 px-3 py-2 text-sm font-semibold text-white hover:bg-sky-700">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                Нов извор
            </a>
        </header>

        @if ($sources->isEmpty())
            <div class="rounded-xl border-2 border-dashed border-slate-300 bg-white p-10 text-center">
                <div class="mx-auto mb-3 h-12 w-12 rounded-full bg-sky-100 text-sky-700 flex items-center justify-center">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/></svg>
                </div>
                <h3 class="text-lg font-semibold text-slate-900">Сè уште нема извор</h3>
                <p class="mt-1 text-sm text-slate-600 max-w-md mx-auto">
                    Додади URL на твојата „Last Minute" страница (пример: <code class="bg-slate-100 px-1.5 py-0.5 rounded text-xs">aries.mk/st_hotel/last-minute-corner</code>) и AI веднаш ќе извлече понуди.
                </p>
                <a href="{{ route('agency.import-sources.create') }}" class="btn-primary mt-5">+ Додај извор</a>
            </div>
        @else
            <div class="space-y-3">
                @foreach ($sources as $source)
                    <a href="{{ route('agency.import-sources.show', $source) }}"
                        class="block rounded-xl border border-slate-200 bg-white p-4 shadow-sm hover:border-sky-300 hover:shadow-md transition">
                        <div class="flex items-center justify-between gap-3 flex-wrap">
                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <h3 class="font-semibold text-slate-900 truncate">
                                        {{ $source->label ?: parse_url($source->url, PHP_URL_HOST) }}
                                    </h3>
                                    @if (! $source->active)
                                        <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600">паузиран</span>
                                    @endif
                                </div>
                                <div class="text-xs text-slate-500 truncate">{{ $source->url }}</div>
                                <div class="mt-1.5 flex items-center gap-3 text-xs">
                                    <span class="text-slate-500">{{ \App\Models\ImportSource::SCHEDULES[$source->schedule] }}</span>
                                    @if ($source->last_synced_at)
                                        <span class="text-slate-400">·</span>
                                        <span class="text-slate-500">Последно: {{ $source->last_synced_at->diffForHumans() }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="shrink-0 text-right">
                                @if ($source->last_status === 'success')
                                    <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 text-emerald-700 px-2.5 py-1 text-xs font-medium">
                                        ✓ {{ $source->last_extracted_count }} нови
                                    </span>
                                @elseif ($source->last_status === 'partial')
                                    <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 text-slate-700 px-2.5 py-1 text-xs font-medium">
                                        Нема нови
                                    </span>
                                @elseif ($source->last_status === 'failed')
                                    <span class="inline-flex items-center gap-1 rounded-full bg-red-50 text-red-700 px-2.5 py-1 text-xs font-medium">
                                        ✕ грешка
                                    </span>
                                @else
                                    <span class="text-xs text-slate-400">сè уште без sync</span>
                                @endif
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
@endsection
