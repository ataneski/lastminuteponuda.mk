@extends('layouts.app')

@section('title', $listing->title.' — lastminuteponuda.mk')
@section('description', \Illuminate\Support\Str::limit($listing->description, 150))

@section('content')
    <article class="mx-auto max-w-4xl px-4 py-8">
        <a href="{{ route('listings.index') }}" class="text-sm text-sky-700 hover:underline">← Назад на огласи</a>

        <div class="mt-4 overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
            @if ($listing->image_url)
                <img src="{{ $listing->image_url }}" alt="{{ $listing->title }}" class="h-72 w-full object-cover">
            @else
                <div class="h-56 bg-gradient-to-br from-sky-100 to-sky-50"></div>
            @endif

            <div class="p-6">
                <div class="flex items-start justify-between gap-4 flex-wrap">
                    <div>
                        <h1 class="text-2xl font-bold text-slate-900">{{ $listing->title }}</h1>
                        <p class="mt-1 text-slate-600">
                            {{ $listing->destination }}, {{ $listing->country }} ·
                            <span class="font-medium">{{ $listing->hotel_name }}</span>
                            <span class="text-amber-500">{{ str_repeat('★', $listing->hotel_stars) }}</span>
                        </p>
                    </div>
                    <div class="text-right">
                        <div class="text-3xl font-bold text-sky-700">{{ $listing->formatted_price }}</div>
                        <div class="text-sm text-slate-500">по лице</div>
                    </div>
                </div>

                <dl class="mt-6 grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                    <div>
                        <dt class="text-slate-500">Поаѓање</dt>
                        <dd class="mt-0.5 font-medium text-slate-900">{{ $listing->departure_date->format('d.m.Y') }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500">Враќање</dt>
                        <dd class="mt-0.5 font-medium text-slate-900">{{ $listing->return_date->format('d.m.Y') }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500">Ноќевања</dt>
                        <dd class="mt-0.5 font-medium text-slate-900">{{ $listing->nights }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500">Слободни места</dt>
                        <dd class="mt-0.5 font-medium text-slate-900">{{ $listing->available_seats }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500">Пансион</dt>
                        <dd class="mt-0.5 font-medium text-slate-900">{{ $listing->board_label }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500">Превоз</dt>
                        <dd class="mt-0.5 font-medium text-slate-900">{{ $listing->transport_label }}</dd>
                    </div>
                </dl>

                @if (count($listing->features))
                    <section class="mt-6">
                        <h2 class="text-sm font-semibold text-slate-700 mb-2">Карактеристики</h2>
                        <div class="flex flex-wrap gap-1.5">
                            @foreach ($listing->features as $f)
                                <span class="badge">{{ $f }}</span>
                            @endforeach
                        </div>
                    </section>
                @endif

                <section class="mt-6">
                    <h2 class="text-sm font-semibold text-slate-700 mb-2">Опис</h2>
                    <p class="whitespace-pre-line text-slate-700 leading-relaxed">{{ $listing->description }}</p>
                </section>

                <section class="mt-6 rounded-md bg-slate-50 p-4 border border-slate-200">
                    <h2 class="text-sm font-semibold text-slate-700">Контакт со агенцијата</h2>
                    <p class="text-slate-900 font-medium">{{ $listing->agency_name }}</p>
                    <p class="text-slate-700">{{ $listing->agency_contact }}</p>
                </section>
            </div>
        </div>
    </article>
@endsection
