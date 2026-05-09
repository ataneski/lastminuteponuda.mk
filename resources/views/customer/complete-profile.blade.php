@extends('layouts.app')

@section('title', 'Дополни го профилот — lastminuteponuda.mk')

@section('content')
    <div class="mx-auto max-w-md px-4 py-10">
        <header class="mb-6 text-center">
            <h1 class="text-2xl font-bold text-slate-900">Уште еден чекор</h1>
            <p class="mt-2 text-slate-600">
                Здраво, {{ $user->first_name ?: $user->name }}! За да ги видиш цените и да
                контактираш агенции, треба телефон + потврда на име.
            </p>
        </header>

        <form method="POST" action="{{ route('customer.complete-profile.store') }}"
            class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm space-y-4">
            @csrf

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="label">Име</label>
                    <input type="text" name="first_name" class="input" required
                        value="{{ old('first_name', $user->first_name) }}">
                    @error('first_name') <p class="error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label">Презиме</label>
                    <input type="text" name="last_name" class="input" required
                        value="{{ old('last_name', $user->last_name) }}">
                    @error('last_name') <p class="error">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label class="label">Телефон</label>
                <input type="tel" name="phone" class="input" required autofocus
                    value="{{ old('phone') }}" placeholder="+389 70 …">
                @error('phone') <p class="error">{{ $message }}</p> @enderror
            </div>

            <label class="flex items-start gap-2 text-sm text-slate-700">
                <input type="checkbox" name="marketing_consent" value="1" class="mt-0.5"
                    {{ old('marketing_consent', '1') ? 'checked' : '' }}>
                <span>Сакам да примам понуди и newsletter преку email или SMS.</span>
            </label>

            <div class="flex justify-end">
                <button type="submit" class="btn-primary">Заврши →</button>
            </div>
        </form>
    </div>
@endsection
