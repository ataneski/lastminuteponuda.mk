@extends('layouts.app')

@section('title', 'Уредување на оглас — lastminuteponuda.mk')

@section('content')
    <div class="mx-auto max-w-3xl px-4 py-8">
        <header class="mb-6 flex items-end justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Уреди оглас</h1>
                <p class="mt-1 text-slate-600">Промените се применуваат веднаш по зачувување.</p>
            </div>
            <form method="POST" action="{{ route('listings.destroy', $listing) }}"
                onsubmit="return confirm('Сигурно сакате да го избришете овој оглас? Не може да се врати.')">
                @csrf
                @method('DELETE')
                <button type="submit" class="inline-flex items-center justify-center rounded-md border border-red-300 bg-white px-4 py-2 text-sm font-medium text-red-700 hover:bg-red-50">
                    Избриши оглас
                </button>
            </form>
        </header>

        <livewire:listing-form :listing="$listing" />
    </div>
@endsection
