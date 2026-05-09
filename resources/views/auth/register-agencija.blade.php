<x-guest-layout>
    <div class="mb-4">
        <h1 class="text-xl font-semibold text-slate-900">Регистрирај агенција</h1>
        <p class="mt-1 text-sm text-slate-600">
            За туристички агенции што сакаат да поставуваат огласи на платформата.
        </p>
    </div>

    <form method="POST" action="{{ route('register.agency') }}">
        @csrf

        <div>
            <x-input-label for="name" value="Име на агенција" />
            <x-text-input id="name" class="block mt-1 w-full" type="text" name="name"
                :value="old('name')" required autofocus placeholder="пр. Балкан Травел" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="email" value="Email" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email"
                :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="password" value="Лозинка" />
            <x-text-input id="password" class="block mt-1 w-full" type="password" name="password"
                required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="password_confirmation" value="Потврди лозинка" />
            <x-text-input id="password_confirmation" class="block mt-1 w-full" type="password"
                name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center justify-between mt-6">
            <a class="underline text-sm text-slate-600 hover:text-slate-900" href="{{ route('login') }}">
                Веќе имам сметка
            </a>
            <x-primary-button>Регистрирај агенција</x-primary-button>
        </div>

        <p class="mt-4 text-xs text-slate-500">
            Не си агенција?
            <a href="{{ route('register') }}" class="text-sky-700 hover:underline">← Регистрирај се како корисник</a>
        </p>
    </form>
</x-guest-layout>
