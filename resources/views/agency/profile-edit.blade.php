@extends('layouts.app')

@section('title', 'Профил на агенција — lastminuteponuda.mk')

@section('content')
    <div class="mx-auto max-w-3xl px-4 py-8">
        <header class="mb-6">
            <h1 class="text-2xl font-bold text-slate-900">Профил на агенција</h1>
            <p class="mt-1 text-slate-600">
                Поставете лого, бои, контакт и опис. Овие податоци се појавуваат на вашата
                јавна страница и на вашите огласи.
            </p>
        </header>

        <livewire:agency-profile-form />
    </div>
@endsection
