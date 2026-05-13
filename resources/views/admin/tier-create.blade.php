@extends('layouts.admin', ['active' => 'tiers'])

@section('title', 'Нов план — admin')
@section('breadcrumb-leaf', 'Нов')

@section('content')
    <div class="max-w-3xl">
        <h1 class="text-2xl font-semibold text-slate-900 mb-6">Нов план</h1>

        <form method="POST" action="{{ route('admin.tiers.store') }}"
            class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            @csrf
            @include('admin._tier-form', ['tier' => null])

            <div class="mt-6 flex items-center justify-between">
                <a href="{{ route('admin.tiers') }}" class="text-sm text-slate-600 hover:underline">← Назад</a>
                <button type="submit" class="inline-flex items-center rounded-md bg-sky-600 px-4 py-2 text-sm font-semibold text-white hover:bg-sky-700">Создај план</button>
            </div>
        </form>
    </div>
@endsection
