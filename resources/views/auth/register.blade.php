<x-guest-layout>
    <div class="mb-4">
        <h1 class="text-xl font-semibold text-slate-900">Регистрирај се</h1>
        <p class="mt-1 text-sm text-slate-600">
            Регистрирај се за да ги видиш цените и да контактираш со агенциите.
        </p>
    </div>

    <form method="POST" action="{{ route('register') }}">
        @csrf

        <div class="grid gap-3 sm:grid-cols-2">
            <div>
                <x-input-label for="first_name" value="Име" />
                <x-text-input id="first_name" class="block mt-1 w-full" type="text" name="first_name"
                    :value="old('first_name')" required autofocus autocomplete="given-name" />
                <x-input-error :messages="$errors->get('first_name')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="last_name" value="Презиме" />
                <x-text-input id="last_name" class="block mt-1 w-full" type="text" name="last_name"
                    :value="old('last_name')" required autocomplete="family-name" />
                <x-input-error :messages="$errors->get('last_name')" class="mt-2" />
            </div>
        </div>

        <div class="mt-4">
            <x-input-label for="phone" value="Телефон" />
            <x-text-input id="phone" class="block mt-1 w-full" type="tel" name="phone"
                :value="old('phone')" required autocomplete="tel" placeholder="+389 70 …" />
            <x-input-error :messages="$errors->get('phone')" class="mt-2" />
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

        <label class="mt-4 flex items-start gap-2 text-sm text-slate-700">
            <input type="checkbox" name="marketing_consent" value="1" class="mt-0.5"
                {{ old('marketing_consent', '1') ? 'checked' : '' }}>
            <span>
                Сакам да примам понуди и newsletter преку email или SMS.
                Може да се отпишам во секое време.
            </span>
        </label>

        <div class="flex items-center justify-between mt-6">
            <a class="underline text-sm text-slate-600 hover:text-slate-900" href="{{ route('login') }}">
                Веќе имам сметка
            </a>
            <x-primary-button>Регистрирај се</x-primary-button>
        </div>

        <p class="mt-4 text-xs text-slate-500">
            Туристичка агенција?
            <a href="{{ route('register.agency') }}" class="text-sky-700 hover:underline">Регистрирај агенција →</a>
        </p>
    </form>

    <x-oauth-buttons />
</x-guest-layout>
