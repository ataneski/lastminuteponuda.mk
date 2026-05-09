@extends('layouts.app')

@section('title', 'Pro план — lastminuteponuda.mk')

@section('content')
    <div class="mx-auto max-w-5xl px-4 py-10">
        <header class="text-center mb-8">
            <h1 class="text-3xl font-bold text-slate-900">Изберете план</h1>
            <p class="mt-2 text-slate-600">Free план е секогаш бесплатен. Pro и Premium се за агенции што сакаат повеќе видливост.</p>
        </header>

        <div class="grid gap-5 md:grid-cols-3">
            @php $current = auth()->check() ? auth()->user()->effectiveTier() : null; @endphp

            <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm flex flex-col">
                <h2 class="text-lg font-semibold text-slate-900">Free</h2>
                <div class="mt-2 text-3xl font-bold text-slate-900">0 MKD<span class="text-base font-normal text-slate-500">/мес.</span></div>
                <p class="mt-1 text-sm text-slate-500">Максимум 3 активни огласи</p>
                <ul class="mt-4 space-y-2 text-sm text-slate-700 flex-1">
                    <li>✓ До 3 активни огласи</li>
                    <li>✓ Основен профил со лого</li>
                    <li>✗ Без аналитика</li>
                    <li>✗ Без featured boost</li>
                    <li>✗ Без социјални постови</li>
                </ul>
                @if ($current === 'free')
                    <button class="btn-secondary mt-5" disabled>Тековен план</button>
                @endif
            </div>

            <div class="rounded-lg border-2 border-sky-500 bg-white p-6 shadow-md flex flex-col relative">
                <span class="absolute -top-3 left-1/2 -translate-x-1/2 inline-flex items-center rounded-full bg-sky-600 px-3 py-0.5 text-xs font-semibold text-white">Препорачано</span>
                <h2 class="text-lg font-semibold text-slate-900">Pro</h2>
                <div class="mt-2 text-3xl font-bold text-slate-900">990 MKD<span class="text-base font-normal text-slate-500">/мес.</span></div>
                <p class="mt-1 text-sm text-slate-500">Без лимит на огласи</p>
                <ul class="mt-4 space-y-2 text-sm text-slate-700 flex-1">
                    <li>✓ Неограничено огласи</li>
                    <li>✓ Целосно брендирање (cover, custom URL, акцент боја)</li>
                    <li>✓ Аналитика — прегледи, прашања, конверзија</li>
                    <li>✓ 1 Featured boost / месец вклучен</li>
                    <li>✓ 1 IG post / месец од нас</li>
                </ul>
                @if ($current === 'pro')
                    <button class="btn-secondary mt-5" disabled>Тековен план</button>
                @else
                    <a href="mailto:hello@lastminuteponuda.mk?subject=Pro%20план" class="btn-primary mt-5">Контактирај за активирање</a>
                @endif
            </div>

            <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm flex flex-col">
                <h2 class="text-lg font-semibold text-slate-900">Premium</h2>
                <div class="mt-2 text-3xl font-bold text-slate-900">2,490 MKD<span class="text-base font-normal text-slate-500">/мес.</span></div>
                <p class="mt-1 text-sm text-slate-500">За поголеми агенции</p>
                <ul class="mt-4 space-y-2 text-sm text-slate-700 flex-1">
                    <li>✓ Сè од Pro</li>
                    <li>✓ 4 Featured boost / месец</li>
                    <li>✓ 4 IG posts + 1 story / месец</li>
                    <li>✓ Verified badge</li>
                    <li>✓ Приоритетна поддршка</li>
                </ul>
                @if ($current === 'premium')
                    <button class="btn-secondary mt-5" disabled>Тековен план</button>
                @else
                    <a href="mailto:hello@lastminuteponuda.mk?subject=Premium%20план" class="btn-secondary mt-5">Контактирај за активирање</a>
                @endif
            </div>
        </div>

        <section class="mt-12 rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-xl font-semibold text-slate-900">Featured Boost — per oglas</h2>
            <p class="mt-1 text-slate-600 text-sm">Доплата за поединечен оглас да биде на врвот на резултатите.</p>

            <div class="mt-4 grid gap-4 md:grid-cols-3">
                <div class="rounded-md border border-slate-200 p-4">
                    <h3 class="font-semibold text-slate-900">Boost 7</h3>
                    <div class="mt-1 text-2xl font-bold">300 MKD</div>
                    <p class="mt-1 text-xs text-slate-500">7 дена топ позиција</p>
                </div>
                <div class="rounded-md border border-slate-200 p-4">
                    <h3 class="font-semibold text-slate-900">Boost 30</h3>
                    <div class="mt-1 text-2xl font-bold">700 MKD</div>
                    <p class="mt-1 text-xs text-slate-500">30 дена топ позиција</p>
                </div>
                <div class="rounded-md border border-amber-300 p-4 bg-amber-50">
                    <h3 class="font-semibold text-slate-900">Boost + Social</h3>
                    <div class="mt-1 text-2xl font-bold">1,200 MKD</div>
                    <p class="mt-1 text-xs text-slate-500">30 дена + IG post + story</p>
                </div>
            </div>
            <p class="mt-4 text-xs text-slate-500">
                Активирањето е тековно мануелно — јавете ни се на email. Self-service плаќање доаѓа во Q4.
            </p>
        </section>
    </div>
@endsection
