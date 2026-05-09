@extends('layouts.app')

@section('title', 'Нов оглас со AI — lastminuteponuda.mk')

@section('content')
    <div class="mx-auto max-w-3xl px-4 py-8">
        <header class="mb-6">
            <h1 class="text-2xl font-bold text-slate-900">Нов оглас со AI помош</h1>
            <p class="mt-1 text-slate-600">
                Прикачете слики, пополнете ги клучните бројки, и нашата AI асистенција ќе го
                подреди најдобриот редослед на сликите и ќе предложи наслов. Можете сè да
                го промените пред објавување.
            </p>
            <p class="mt-2 text-xs text-slate-500">
                Сакате стара рачна форма? <a href="{{ route('listings.create') }}" class="text-sky-700 hover:underline">Поставете ракум →</a>
            </p>
        </header>

        <livewire:ai-listing-wizard />
    </div>
@endsection
