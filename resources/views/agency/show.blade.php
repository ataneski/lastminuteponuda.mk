@extends('layouts.app')

@section('title', $agency->brand_name.' — Last minute понуди')
@section('description', \Illuminate\Support\Str::limit($agency->tagline ?: $agency->description ?? 'Last minute понуди од '.$agency->brand_name, 150))
@section('og_type', 'profile')
@if ($agency->logo_url)
    @section('og_image', \Illuminate\Support\Str::startsWith($agency->logo_url, 'http') ? $agency->logo_url : url($agency->logo_url))
@endif

@push('head')
    <script type="application/ld+json">
        {!! json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'TravelAgency',
            'name' => $agency->brand_name,
            'description' => $agency->description,
            'url' => route('agency.show', $agency),
            'logo' => $agency->logo_url ? (\Illuminate\Support\Str::startsWith($agency->logo_url, 'http') ? $agency->logo_url : url($agency->logo_url)) : null,
            'telephone' => $agency->phone,
            'address' => $agency->address ? ['@type' => 'PostalAddress', 'streetAddress' => $agency->address] : null,
            'sameAs' => $agency->website ? [$agency->website] : [],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
    </script>
@endpush

@section('content')
    @php $accent = $agency->accent; @endphp

    <section class="relative" style="background-color: {{ $accent }};">
        @if ($agency->cover_url)
            <img src="{{ $agency->cover_url }}" alt="" class="absolute inset-0 h-full w-full object-cover opacity-50">
        @endif
        <div class="relative mx-auto max-w-6xl px-4 py-12 text-white">
            <div class="flex items-center gap-5 flex-wrap">
                @if ($agency->logo_url)
                    <img src="{{ $agency->logo_url }}" alt="{{ $agency->brand_name }}"
                        class="h-20 w-20 rounded-lg bg-white p-1.5 object-contain shadow-md">
                @else
                    <div class="h-20 w-20 rounded-lg bg-white/20 flex items-center justify-center text-3xl font-bold shadow-md">
                        {{ mb_substr($agency->brand_name, 0, 1) }}
                    </div>
                @endif
                <div>
                    <h1 class="text-3xl md:text-4xl font-bold leading-tight">{{ $agency->brand_name }}</h1>
                    @if ($agency->tagline)
                        <p class="mt-1 text-lg opacity-90">{{ $agency->tagline }}</p>
                    @endif
                </div>
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-6xl px-4 py-8 grid gap-8 md:grid-cols-[2fr_1fr]">
        <div>
            <h2 class="text-xl font-semibold text-slate-900 mb-4">
                Активни понуди
                <span class="ml-1 text-sm font-normal text-slate-500">({{ $listings->total() }})</span>
            </h2>

            @if ($listings->isEmpty())
                <div class="rounded-lg border-2 border-dashed border-slate-300 bg-white p-10 text-center">
                    <p class="text-slate-600">Оваа агенција нема активни огласи моментално.</p>
                </div>
            @else
                <div class="grid gap-4 sm:grid-cols-2">
                    @foreach ($listings as $listing)
                        <x-listing-card :listing="$listing" />
                    @endforeach
                </div>

                <div class="mt-6">{{ $listings->links() }}</div>
            @endif
        </div>

        <aside class="space-y-4">
            @if ($agency->description)
                <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <h3 class="text-sm font-semibold text-slate-700 mb-2">За агенцијата</h3>
                    <p class="whitespace-pre-line text-slate-700 leading-relaxed text-sm">{{ $agency->description }}</p>
                </div>
            @endif

            <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="text-sm font-semibold text-slate-700 mb-3">Контакт</h3>
                <dl class="space-y-2 text-sm">
                    @if ($agency->phone)
                        <div>
                            <dt class="text-slate-500">Телефон</dt>
                            <dd><a href="tel:{{ preg_replace('/\s+/', '', $agency->phone) }}"
                                class="font-medium hover:underline" style="color: {{ $accent }};">{{ $agency->phone }}</a></dd>
                        </div>
                    @endif
                    <div>
                        <dt class="text-slate-500">Email</dt>
                        <dd><a href="mailto:{{ $agency->email }}"
                            class="font-medium hover:underline" style="color: {{ $accent }};">{{ $agency->email }}</a></dd>
                    </div>
                    @if ($agency->website)
                        <div>
                            <dt class="text-slate-500">Веб-сајт</dt>
                            <dd><a href="{{ $agency->website }}" target="_blank" rel="noopener"
                                class="font-medium hover:underline break-all" style="color: {{ $accent }};">{{ $agency->website }}</a></dd>
                        </div>
                    @endif
                    @if ($agency->address)
                        <div>
                            <dt class="text-slate-500">Адреса</dt>
                            <dd class="font-medium text-slate-900">{{ $agency->address }}</dd>
                        </div>
                    @endif
                </dl>
                @if ($agency->instagram_handle || $agency->facebook_url)
                    <div class="mt-3 flex gap-2">
                        @if ($agency->instagram_handle)
                            <a href="https://instagram.com/{{ $agency->instagram_handle }}" target="_blank" rel="noopener"
                                class="inline-flex items-center rounded-md border border-slate-300 bg-white px-3 py-1 text-xs font-medium text-slate-700 hover:bg-slate-50">
                                Instagram
                            </a>
                        @endif
                        @if ($agency->facebook_url)
                            <a href="{{ $agency->facebook_url }}" target="_blank" rel="noopener"
                                class="inline-flex items-center rounded-md border border-slate-300 bg-white px-3 py-1 text-xs font-medium text-slate-700 hover:bg-slate-50">
                                Facebook
                            </a>
                        @endif
                    </div>
                @endif
            </div>
        </aside>
    </section>
@endsection
