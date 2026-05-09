@extends('layouts.app')

@section('title', 'Сите огласи — lastminuteponuda.mk')

@section('content')
    <div class="mx-auto max-w-6xl px-4 py-8">
        <header class="mb-6 flex items-end justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Сите огласи</h1>
                <p class="text-slate-600">Пронајди last minute понуда која ти одговара.</p>
            </div>
            <a href="{{ route('listings.create') }}" class="btn-primary">+ Нов оглас</a>
        </header>

        <livewire:listings-index />
    </div>
@endsection
