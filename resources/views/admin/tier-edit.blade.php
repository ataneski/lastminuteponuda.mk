@extends('layouts.admin', ['active' => 'tiers'])

@section('title', 'Уреди план ' . $tier->key . ' — admin')
@section('breadcrumb-leaf', $tier->key)

@section('content')
    <div class="max-w-3xl">
        <h1 class="text-2xl font-semibold text-slate-900 mb-6">Уреди план: {{ $tier->name }}</h1>

        <form method="POST" action="{{ route('admin.tiers.update', $tier) }}"
            class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            @csrf
            @method('PATCH')
            @include('admin._tier-form', ['tier' => $tier])

            <div class="mt-6 flex items-center justify-between">
                <a href="{{ route('admin.tiers') }}" class="text-sm text-slate-600 hover:underline">← Назад</a>
                <button type="submit" class="inline-flex items-center rounded-md bg-sky-600 px-4 py-2 text-sm font-semibold text-white hover:bg-sky-700">Зачувај</button>
            </div>
        </form>
    </div>
@endsection
