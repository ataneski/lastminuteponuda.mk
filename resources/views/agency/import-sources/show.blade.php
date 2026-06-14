@extends('layouts.app')

@section('title', ($source->label ?: parse_url($source->url, PHP_URL_HOST)) . ' — auto-import')

@section('content')
    <div class="mx-auto max-w-3xl px-4 py-8">
        <a href="{{ route('agency.import-sources') }}" class="text-sm text-sky-700 hover:underline">← Сите извори</a>

        <header class="mt-3 mb-6 flex items-end justify-between flex-wrap gap-3">
            <div class="min-w-0">
                <h1 class="text-2xl font-semibold text-slate-900 truncate">
                    {{ $source->label ?: parse_url($source->url, PHP_URL_HOST) }}
                </h1>
                <a href="{{ $source->url }}" target="_blank" rel="noopener"
                    class="text-sm text-sky-700 hover:underline break-all">{{ $source->url }} ↗</a>
            </div>
            <div class="flex items-center gap-2">
                <form method="POST" action="{{ route('agency.import-sources.sync', $source) }}">
                    @csrf
                    <button type="submit"
                        class="inline-flex items-center gap-1.5 rounded-md bg-sky-600 px-4 py-2 text-sm font-semibold text-white hover:bg-sky-700">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99"/></svg>
                        Sync сега
                    </button>
                </form>
            </div>
        </header>

        <div class="grid gap-4 md:grid-cols-2 mb-6">
            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="text-xs uppercase tracking-wide text-slate-500">Распоред</div>
                <div class="mt-1 text-lg font-semibold text-slate-900">
                    {{ \App\Models\ImportSource::SCHEDULES[$source->schedule] }}
                </div>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="text-xs uppercase tracking-wide text-slate-500">Статус</div>
                <div class="mt-1">
                    @if ($source->active)
                        <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 text-emerald-800 px-2.5 py-0.5 text-sm font-semibold">Активен</span>
                    @else
                        <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 text-slate-700 px-2.5 py-0.5 text-sm font-semibold">Паузиран</span>
                    @endif
                </div>
            </div>
        </div>

        @if ($source->last_synced_at)
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm mb-6">
                <h2 class="text-sm font-semibold text-slate-900 mb-3">Последна синхронизација</h2>
                <dl class="grid gap-3 sm:grid-cols-2 text-sm">
                    <div>
                        <dt class="text-xs text-slate-500">Кога</dt>
                        <dd class="font-medium text-slate-900">{{ $source->last_synced_at->format('d.m.Y H:i') }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-500">Резултат</dt>
                        <dd>
                            @if ($source->last_status === 'success')
                                <span class="text-emerald-700 font-semibold">✓ {{ $source->last_extracted_count }} нови огласи (draft)</span>
                            @elseif ($source->last_status === 'partial')
                                <span class="text-slate-600">Нема нови — сите се веќе додадени</span>
                            @else
                                <span class="text-red-700 font-semibold">✕ Не успеа</span>
                            @endif
                        </dd>
                    </div>
                    @if ($source->last_error)
                        <div class="sm:col-span-2">
                            <dt class="text-xs text-slate-500">Грешка</dt>
                            <dd class="font-mono text-xs text-red-700 bg-red-50 p-2 rounded">{{ $source->last_error }}</dd>
                        </div>
                    @endif
                    @if ($source->consecutive_failures > 0)
                        <div class="sm:col-span-2">
                            <p class="text-xs text-amber-800 bg-amber-50 border border-amber-200 p-2 rounded">
                                {{ $source->consecutive_failures }} последователни неуспеси.
                                @if ($source->consecutive_failures >= 3)
                                    Изворот е автоматски паузиран — провери го URL-от и активирај го пак.
                                @endif
                            </p>
                        </div>
                    @endif
                </dl>
            </div>
        @endif

        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-sm font-semibold text-slate-900 mb-3">Управување</h2>
            <div class="flex items-center justify-between gap-3 flex-wrap">
                <form method="POST" action="{{ route('agency.import-sources.toggle', $source) }}">
                    @csrf
                    <button type="submit" class="text-sm font-medium text-slate-700 hover:underline">
                        {{ $source->active ? 'Паузирај' : 'Активирај' }}
                    </button>
                </form>
                <a href="{{ route('listings.mine') }}" class="text-sm font-medium text-sky-700 hover:underline">
                    Прегледи draft огласи →
                </a>
                <form method="POST" action="{{ route('agency.import-sources.destroy', $source) }}"
                    onsubmit="return confirm('Сигурно избриши го овој извор? Огласите остануваат.')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-sm font-medium text-red-700 hover:underline">
                        Избриши извор
                    </button>
                </form>
            </div>
        </div>
    </div>
@endsection
