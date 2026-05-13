@extends('layouts.app')

@section('title', 'Admin — Нова агенција')

@section('content')
    <div class="mx-auto max-w-2xl px-4 py-8">
        <h1 class="text-2xl font-bold text-slate-900 mb-2">Нова агенција</h1>
        @include('admin._nav', ['active' => 'agencies'])

        <form method="POST" action="{{ route('admin.agencies.store') }}"
            class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm space-y-4">
            @csrf

            <div>
                <label class="label">Име на агенција <span class="text-red-500">*</span></label>
                <input type="text" name="name" class="input" required value="{{ old('name') }}">
                @error('name') <p class="error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="label">Email <span class="text-red-500">*</span></label>
                <input type="email" name="email" class="input" required value="{{ old('email') }}">
                @error('email') <p class="error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="label">Телефон</label>
                <input type="tel" name="phone" class="input" value="{{ old('phone') }}">
                @error('phone') <p class="error">{{ $message }}</p> @enderror
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="label">Tier <span class="text-red-500">*</span></label>
                    <select name="subscription_tier" class="input">
                        @foreach ($tiers as $t)
                            <option value="{{ $t->key }}" {{ old('subscription_tier', 'free') === $t->key ? 'selected' : '' }}>
                                {{ $t->name }} ({{ $t->monthly_price }} {{ $t->currency }}/мес.)
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="label">Tier до</label>
                    <input type="date" name="subscription_until" class="input" value="{{ old('subscription_until') }}">
                </div>
            </div>

            <div>
                <label class="label">Лозинка (празно = автоматска)</label>
                <input type="text" name="password" class="input" placeholder="Auto-generated"
                    value="{{ old('password') }}">
                @error('password') <p class="error">{{ $message }}</p> @enderror
                <p class="mt-1 text-xs text-slate-500">
                    Ако оставиш празно, се генерира 12-знаков string. Ќе биде покажан еднаш во порака после создавање — копирај го и испрати го на агенцијата.
                </p>
            </div>

            <div class="flex items-center justify-between">
                <a href="{{ route('admin.agencies') }}" class="text-sm text-slate-600 hover:underline">← Назад</a>
                <button type="submit" class="btn-primary">Создај</button>
            </div>
        </form>
    </div>
@endsection
