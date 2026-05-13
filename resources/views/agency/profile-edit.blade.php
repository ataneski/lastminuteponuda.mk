@extends('layouts.app')

@section('title', 'Профил на агенција — lastminuteponuda.mk')

@section('content')
    <div class="mx-auto max-w-7xl px-4 py-8">
        <header class="mb-8 flex items-end justify-between flex-wrap gap-3">
            <div>
                <h1 class="text-2xl font-semibold text-slate-900">Профил на агенција</h1>
                <p class="mt-1 text-slate-600 text-sm">
                    Поставете лого, бои, контакт и опис. Се појавуваат на вашата јавна
                    страница и на огласите.
                </p>
            </div>
            <a href="{{ route('agency.show', auth()->user()) }}" target="_blank" rel="noopener"
                class="inline-flex items-center gap-1.5 rounded-md border border-slate-300 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/></svg>
                Прегледај јавна страница
            </a>
        </header>

        <livewire:agency-profile-form />
    </div>
@endsection
