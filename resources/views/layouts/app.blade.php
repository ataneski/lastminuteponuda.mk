<!DOCTYPE html>
<html lang="mk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'lastminuteponuda.mk — Last minute понуди од македонски агенции')</title>
    <meta name="description" content="@yield('description', 'Платформа за last minute туристички понуди. Агенциите брзо и лесно поставуваат огласи со цени и карактеристики.')">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen flex flex-col bg-slate-50 text-slate-900 antialiased">
    <header class="bg-white border-b border-slate-200">
        <div class="mx-auto max-w-6xl px-4 py-4 flex items-center justify-between">
            <a href="{{ route('home') }}" class="flex items-center gap-2">
                <span class="text-xl font-bold text-sky-700">lastminuteponuda</span>
                <span class="text-xl font-bold text-slate-400">.mk</span>
            </a>
            <nav class="flex items-center gap-3">
                <a href="{{ route('listings.index') }}" class="text-sm font-medium text-slate-600 hover:text-slate-900">Огласи</a>

                @auth
                    <a href="{{ route('listings.mine') }}" class="text-sm font-medium text-slate-600 hover:text-slate-900">Мои огласи</a>
                    <a href="{{ route('listings.create') }}" class="btn-primary">+ Нов оглас</a>
                    <form method="POST" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <button type="submit" class="text-sm font-medium text-slate-600 hover:text-slate-900">
                            Одјави се
                        </button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="text-sm font-medium text-slate-600 hover:text-slate-900">Најави се</a>
                    <a href="{{ route('register') }}" class="btn-primary">Регистрирај агенција</a>
                @endauth
            </nav>
        </div>
    </header>

    <main class="flex-1">
        @if (session('status'))
            <div class="mx-auto max-w-6xl px-4 pt-4">
                <div class="rounded-md border border-emerald-300 bg-emerald-50 p-3 text-sm text-emerald-800">
                    {{ session('status') }}
                </div>
            </div>
        @endif

        @yield('content')
    </main>

    <footer class="bg-white border-t border-slate-200 mt-12">
        <div class="mx-auto max-w-6xl px-4 py-6 text-sm text-slate-500">
            © {{ date('Y') }} lastminuteponuda.mk — Last minute понуди за вашите патувања.
        </div>
    </footer>

    @livewireScripts
</body>
</html>
