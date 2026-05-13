@extends('layouts.app')

@section('title', 'Admin — Уреди tier ' . $tier->key)

@section('content')
    <div class="mx-auto max-w-3xl px-4 py-8">
        <h1 class="text-2xl font-bold text-slate-900 mb-2">Уреди tier: {{ $tier->name }}</h1>
        @include('admin._nav', ['active' => 'tiers'])

        <form method="POST" action="{{ route('admin.tiers.update', $tier) }}"
            class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            @csrf
            @method('PATCH')
            @include('admin._tier-form', ['tier' => $tier])

            <div class="mt-6 flex items-center justify-between">
                <a href="{{ route('admin.tiers') }}" class="text-sm text-slate-600 hover:underline">← Назад</a>
                <button type="submit" class="btn-primary">Зачувај</button>
            </div>
        </form>
    </div>
@endsection
