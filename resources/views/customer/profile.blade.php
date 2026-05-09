@extends('layouts.app')

@section('title', 'Мој профил — lastminuteponuda.mk')

@section('content')
    <div class="mx-auto max-w-2xl px-4 py-8">
        <header class="mb-6">
            <h1 class="text-2xl font-bold text-slate-900">Мој профил</h1>
            <p class="mt-1 text-slate-600">Уредете ги вашите контакт податоци.</p>
        </header>

        <form method="POST" action="{{ route('customer.profile.update') }}"
            class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm space-y-4">
            @csrf
            @method('PATCH')

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
                <input type="tel" name="phone" class="input" required
                    value="{{ old('phone', $user->phone) }}">
                @error('phone') <p class="error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="label">Email</label>
                <input type="email" name="email" class="input" required
                    value="{{ old('email', $user->email) }}">
                @error('email') <p class="error">{{ $message }}</p> @enderror
            </div>

            <label class="flex items-start gap-2 text-sm text-slate-700">
                <input type="checkbox" name="marketing_consent" value="1" class="mt-0.5"
                    {{ old('marketing_consent', $user->marketing_consent) ? 'checked' : '' }}>
                <span>Сакам да примам понуди и newsletter преку email или SMS.</span>
            </label>

            <div class="flex justify-end">
                <button type="submit" class="btn-primary">Зачувај</button>
            </div>
        </form>
    </div>
@endsection
