<!DOCTYPE html>
<html lang="mk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin — lastminuteponuda.mk')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-slate-50 text-slate-900 antialiased">
    @php
        $active = $active ?? 'index';
        $sections = [
            'index'    => ['Преглед',    'admin.index',    'chart-bar'],
            'agencies' => ['Агенции',    'admin.agencies', 'building-storefront'],
            'listings' => ['Огласи',     'admin.listings', 'rectangle-stack'],
            'tiers'    => ['Планови',    'admin.tiers',    'sparkles'],
        ];

        // Inline SVG icons (Heroicons outline, 20×20). Keeps the bundle
        // free of icon-library dependencies.
        $icons = [
            'chart-bar'           => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z"/>',
            'building-storefront' => '<path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 0 1 .75-.75h3a.75.75 0 0 1 .75.75V21M3 3h18M3 7h18M3 11h18M5 21h14a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2Z"/>',
            'rectangle-stack'     => '<path stroke-linecap="round" stroke-linejoin="round" d="M6 6.878V6a2.25 2.25 0 0 1 2.25-2.25h7.5A2.25 2.25 0 0 1 18 6v.878m-12 0c.235-.083.487-.128.75-.128h10.5c.263 0 .515.045.75.128m-12 0A2.25 2.25 0 0 0 4.5 9v.878m13.5-3A2.25 2.25 0 0 1 19.5 9v.878m0 0c.235.083.487.128.75.128h.375c.621 0 1.125.504 1.125 1.125v10.5C21.75 22.41 20.66 23.5 19.5 23.5h-15c-1.16 0-2.25-1.09-2.25-2.25v-10.5C2.25 10.629 2.754 10.125 3.375 10.125h.375c.263 0 .515-.045.75-.128m13.5 0V11.25a2.25 2.25 0 0 1-2.25 2.25h-9a2.25 2.25 0 0 1-2.25-2.25V9.878"/>',
            'sparkles'            => '<path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09Zm6.187-1.654L15.75 15.5l-.25-1.25a3 3 0 0 0-2-2L12.25 12l1.25-.25a3 3 0 0 0 2-2l.25-1.25.25 1.25a3 3 0 0 0 2 2l1.25.25-1.25.25a3 3 0 0 0-2 2Z"/>',
        ];
    @endphp

    <div class="flex min-h-screen">
        {{-- ─── Sidebar ────────────────────────────────────────────────── --}}
        <aside class="hidden lg:flex w-64 shrink-0 flex-col bg-slate-900 text-slate-100">
            <div class="px-5 py-4 border-b border-slate-800">
                <a href="{{ route('home') }}" class="flex items-center gap-2">
                    <div class="h-8 w-8 rounded-lg bg-gradient-to-br from-sky-400 to-sky-600 flex items-center justify-center font-bold text-white">L</div>
                    <div>
                        <div class="text-sm font-semibold leading-tight">lastminute</div>
                        <div class="text-xs text-slate-400 leading-tight">admin панел</div>
                    </div>
                </a>
            </div>

            <nav class="flex-1 px-3 py-4 space-y-0.5">
                @foreach ($sections as $key => [$label, $routeName, $icon])
                    @php $isActive = $active === $key; @endphp
                    <a href="{{ route($routeName) }}"
                        class="group flex items-center gap-3 rounded-md px-3 py-2 text-sm transition
                            {{ $isActive ? 'bg-sky-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                        <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                            {!! $icons[$icon] !!}
                        </svg>
                        <span>{{ $label }}</span>
                    </a>
                @endforeach
            </nav>

            <div class="border-t border-slate-800 px-5 py-4">
                <a href="{{ route('home') }}" class="block text-xs text-slate-400 hover:text-white mb-2">← На сајтот</a>
                <div class="flex items-center gap-2">
                    <div class="h-8 w-8 rounded-full bg-slate-700 flex items-center justify-center text-xs font-bold">
                        {{ mb_substr(auth()->user()->name, 0, 1) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-medium truncate">{{ auth()->user()->name }}</div>
                        <div class="text-xs text-slate-400 truncate">{{ auth()->user()->email }}</div>
                    </div>
                </div>
                <form method="POST" action="{{ route('logout') }}" class="mt-3">
                    @csrf
                    <button type="submit" class="w-full text-xs text-slate-400 hover:text-white text-left">Одјави се</button>
                </form>
            </div>
        </aside>

        {{-- ─── Content ────────────────────────────────────────────────── --}}
        <div class="flex-1 min-w-0 flex flex-col">
            {{-- Mobile sidebar toggle + breadcrumb --}}
            <header class="sticky top-0 z-10 bg-white border-b border-slate-200 px-4 lg:px-8 py-3 flex items-center justify-between">
                <div class="flex items-center gap-3 min-w-0">
                    <button id="admin-mobile-toggle" class="lg:hidden text-slate-600 hover:text-slate-900 -ml-1">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/>
                        </svg>
                    </button>
                    <div class="text-sm text-slate-500 truncate">
                        Admin <span class="text-slate-300 mx-1">/</span>
                        <span class="text-slate-900 font-medium">{{ $sections[$active][0] ?? '' }}</span>
                        @hasSection('breadcrumb-leaf')
                            <span class="text-slate-300 mx-1">/</span>
                            <span class="text-slate-700">@yield('breadcrumb-leaf')</span>
                        @endif
                    </div>
                </div>
                @hasSection('actions')
                    <div class="flex items-center gap-2 shrink-0">@yield('actions')</div>
                @endif
            </header>

            {{-- Mobile sidebar drawer --}}
            <div id="admin-mobile-sidebar" class="hidden lg:hidden fixed inset-0 z-30">
                <div class="absolute inset-0 bg-black/50" onclick="document.getElementById('admin-mobile-sidebar').classList.add('hidden')"></div>
                <div class="absolute left-0 top-0 bottom-0 w-64 bg-slate-900 text-slate-100 p-3 overflow-y-auto">
                    @foreach ($sections as $key => [$label, $routeName, $icon])
                        @php $isActive = $active === $key; @endphp
                        <a href="{{ route($routeName) }}"
                            class="flex items-center gap-3 rounded-md px-3 py-2 text-sm
                                {{ $isActive ? 'bg-sky-600 text-white' : 'text-slate-300 hover:bg-slate-800' }}">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">{!! $icons[$icon] !!}</svg>
                            <span>{{ $label }}</span>
                        </a>
                    @endforeach
                </div>
            </div>

            <main class="flex-1 px-4 lg:px-8 py-6 lg:py-8">
                @if (session('status'))
                    <div class="mb-4 rounded-md border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 flex items-start gap-2">
                        <svg class="h-5 w-5 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.857-9.809a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z" clip-rule="evenodd"/></svg>
                        <div>{{ session('status') }}</div>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mb-4 rounded-md border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-800">
                        <strong>Грешка:</strong> {{ $errors->first() }}
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    @livewireScripts
    <script>
        document.getElementById('admin-mobile-toggle')?.addEventListener('click', () => {
            document.getElementById('admin-mobile-sidebar').classList.remove('hidden');
        });
    </script>
</body>
</html>
