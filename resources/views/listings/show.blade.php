@extends('layouts.app')

@section('title', $listing->title.' — lastminuteponuda.mk')
@section('description', \Illuminate\Support\Str::limit($listing->description, 150))
@section('og_type', 'product')
@if ($listing->image_url)
    @section('og_image', \Illuminate\Support\Str::startsWith($listing->image_url, 'http') ? $listing->image_url : url($listing->image_url))
@endif

@push('head')
    <script type="application/ld+json">
        {!! json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'TouristTrip',
            'name' => $listing->title,
            'description' => \Illuminate\Support\Str::limit($listing->description, 300),
            'url' => route('listings.show', $listing),
            'image' => $listing->image_url
                ? (\Illuminate\Support\Str::startsWith($listing->image_url, 'http') ? $listing->image_url : url($listing->image_url))
                : null,
            'itinerary' => [
                '@type' => 'Place',
                'name' => $listing->destination,
                'address' => [
                    '@type' => 'PostalAddress',
                    'addressCountry' => $listing->country,
                ],
            ],
            'offers' => [
                '@type' => 'Offer',
                'price' => $listing->price_per_person,
                'priceCurrency' => $listing->currency,
                'availability' => $listing->available_seats > 0
                    ? 'https://schema.org/InStock'
                    : 'https://schema.org/OutOfStock',
                'validFrom' => $listing->created_at->toAtomString(),
                'validThrough' => ($listing->expires_at ?? $listing->return_date)->toAtomString(),
                'seller' => [
                    '@type' => 'TravelAgency',
                    'name' => $listing->agency_name,
                ],
            ],
            'startDate' => $listing->departure_date->toDateString(),
            'endDate' => $listing->return_date->toDateString(),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
    </script>
@endpush

@section('content')
    <article class="mx-auto max-w-4xl px-4 py-8">
        <div class="flex items-center justify-between flex-wrap gap-2">
            <a href="{{ route('listings.index') }}" class="text-sm text-sky-700 hover:underline">← Назад на огласи</a>
            <div class="flex items-center gap-3 flex-wrap">
                @can('update', $listing)
                    <a href="{{ route('listings.analytics', $listing) }}"
                        class="text-sm font-medium text-sky-700 hover:underline">📊 Аналитика</a>
                    <a href="{{ route('listings.social-card', $listing) }}" download
                        class="text-sm font-medium text-sky-700 hover:underline">
                        ⤓ Сподели на IG (1080×1080)
                    </a>
                    <a href="{{ route('listings.edit', $listing) }}"
                        class="text-sm font-medium text-sky-700 hover:underline">Уреди →</a>
                @endcan
            </div>
        </div>

        @if ($listing->isFeatured())
            <div class="mt-4 inline-flex items-center gap-1.5 rounded-full bg-amber-50 border border-amber-200 px-3 py-1 text-xs font-semibold text-amber-800">
                ★ Препорачана понуда
            </div>
        @endif

        <div class="mt-4 overflow-hidden rounded-lg border {{ $listing->isFeatured() ? 'border-amber-300 ring-1 ring-amber-200' : 'border-slate-200' }} bg-white shadow-sm">
            @php $primary = $listing->primary_image_url; @endphp
            @if ($primary)
                <img src="{{ $primary }}" alt="{{ $listing->title }}" class="h-72 w-full object-cover">
            @else
                <div class="h-56 bg-gradient-to-br from-sky-100 to-sky-50"></div>
            @endif

            @if ($listing->images->count() > 1)
                <div class="grid grid-cols-3 md:grid-cols-5 gap-1 p-1">
                    @foreach ($listing->images->skip(1) as $img)
                        <a href="{{ $img->url }}" target="_blank" rel="noopener">
                            <img src="{{ $img->url }}" alt=""
                                class="h-24 w-full object-cover rounded-sm hover:opacity-90 transition">
                        </a>
                    @endforeach
                </div>
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
                        @auth
                            <div class="text-3xl font-bold text-sky-700">{{ $listing->formatted_price }}</div>
                            <div class="text-sm text-slate-500">по лице</div>
                        @else
                            <div class="text-2xl font-semibold text-slate-400 select-none" aria-hidden="true">●●● {{ $listing->currency }}</div>
                            <a href="{{ route('register') }}"
                                class="inline-block mt-1 text-xs text-white bg-sky-600 hover:bg-sky-700 px-3 py-1 rounded-md font-medium">
                                Регистрирај се за цена
                            </a>
                        @endauth
                    </div>
                </div>

                @guest
                    <div class="mt-6 rounded-md border border-sky-200 bg-sky-50 p-4 text-sm text-sky-900">
                        <strong>Цените и контактот се видливи само за регистрирани корисници.</strong>
                        Регистрирајте се за 30 секунди со име, телефон и email и веднаш ќе ги видите.
                        <div class="mt-3 flex gap-2 flex-wrap">
                            <a href="{{ route('register') }}" class="btn-primary">Регистрирај се →</a>
                            <a href="{{ route('login') }}" class="btn-secondary">Веќе имам сметка</a>
                        </div>
                    </div>
                @endguest

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
                    <div class="flex items-center justify-between gap-3 flex-wrap">
                        <div class="flex items-center gap-3">
                            @if ($listing->user?->logo_url)
                                <img src="{{ $listing->user->logo_url }}" alt="" class="h-10 w-10 rounded object-cover">
                            @endif
                            <div>
                                <h2 class="text-sm font-semibold text-slate-700">Контакт со агенцијата</h2>
                                <p class="text-slate-900 font-medium">
                                    {{ $listing->user->brand_name ?? $listing->agency_name }}
                                </p>
                                @auth
                                    <p class="text-slate-700">{{ $listing->agency_contact }}</p>
                                @else
                                    <p class="text-slate-400 select-none" aria-hidden="true">●●●●●●●● ●● ●●</p>
                                    <a href="{{ route('register') }}" class="text-xs text-sky-700 hover:underline">
                                        Регистрирај се за контакт →
                                    </a>
                                @endauth
                            </div>
                        </div>
                        @if ($listing->user)
                            <a href="{{ route('agency.show', $listing->user) }}"
                                class="text-sm font-medium text-sky-700 hover:underline">Сите понуди од оваа агенција →</a>
                        @endif
                    </div>
                </section>

                @auth
                    @if (auth()->id() !== $listing->user_id)
                        <div class="mt-6">
                            <livewire:listing-inquiry :listing="$listing" />
                        </div>
                    @endif
                @else
                    <div class="mt-6 rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                        <h2 class="text-lg font-semibold text-slate-900">Прашајте ја агенцијата</h2>
                        <p class="text-sm text-slate-600 mt-1">
                            За да испратите прашање, потребно е да сте регистриран корисник.
                            Така агенцијата може да ви одговори директно.
                        </p>
                        <div class="mt-4 flex gap-2 flex-wrap">
                            <a href="{{ route('register') }}" class="btn-primary">Регистрирај се →</a>
                            <a href="{{ route('login') }}" class="btn-secondary">Логирај се</a>
                        </div>
                    </div>
                @endauth
            </div>
        </div>
    </article>
@endsection
