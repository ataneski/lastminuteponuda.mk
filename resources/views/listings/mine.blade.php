@extends('layouts.app')

@section('title', 'Мои огласи — lastminuteponuda.mk')

@section('content')
    <div class="mx-auto max-w-6xl px-4 py-8">
        <header class="mb-6 flex items-end justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Мои огласи</h1>
                <p class="text-slate-600">{{ $listings->total() }} огласи поставени од вас.</p>
            </div>
            <a href="{{ route('listings.create') }}" class="btn-primary">+ Нов оглас</a>
        </header>

        @if ($listings->isEmpty())
            <div class="rounded-lg border-2 border-dashed border-slate-300 bg-white p-10 text-center">
                <h3 class="text-lg font-semibold text-slate-800">Сè уште немате објавени огласи</h3>
                <p class="mt-1 text-slate-600">Поставете ја првата last minute понуда.</p>
                <a href="{{ route('listings.create') }}" class="btn-primary mt-4">Поставете оглас</a>
            </div>
        @else
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($listings as $listing)
                    <x-listing-card :listing="$listing" />
                @endforeach
            </div>

            <div class="mt-6">{{ $listings->links() }}</div>
        @endif
    </div>
@endsection
