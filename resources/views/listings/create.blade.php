@extends('layouts.app')

@section('title', 'Нов оглас — lastminuteponuda.mk')

@section('content')
    <div class="mx-auto max-w-3xl px-4 py-8">
        <header class="mb-6">
            <h1 class="text-2xl font-bold text-slate-900">Поставете нов last minute оглас</h1>
            <p class="mt-1 text-slate-600">
                Внесете ги основните податоци за понудата. Целата постапка трае помалку од минута.
            </p>
        </header>

        <livewire:listing-form />
    </div>
@endsection
