@extends('layouts.app')

@section('title', 'Нов auto-import извор')

@section('content')
    <div class="mx-auto max-w-2xl px-4 py-8">
        <a href="{{ route('agency.import-sources') }}" class="text-sm text-sky-700 hover:underline">← Сите извори</a>

        <h1 class="mt-3 text-2xl font-semibold text-slate-900 mb-6">Нов auto-import извор</h1>

        <form method="POST" action="{{ route('agency.import-sources.store') }}"
            class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm space-y-4">
            @csrf

            <div>
                <label class="label">URL на „Last Minute" страница <span class="text-red-500">*</span></label>
                <input type="url" name="url" class="input" required
                    value="{{ old('url') }}"
                    placeholder="https://aries.mk/st_hotel/last-minute-corner/">
                <p class="mt-1 text-xs text-slate-500">
                    Целата страница каде што ги објавувате last-minute понудите.
                </p>
                @error('url') <p class="error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="label">Кратка ознака (опционално)</label>
                <input type="text" name="label" class="input"
                    value="{{ old('label') }}"
                    placeholder="пр. Aries Hotel">
                @error('label') <p class="error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="label">Рапоред на синхронизација</label>
                <select name="schedule" class="input">
                    @foreach (\App\Models\ImportSource::SCHEDULES as $key => $label)
                        <option value="{{ $key }}" {{ old('schedule', '6h') === $key ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-slate-500">
                    Препорачано: на секои 6 часа. „Само рачно" значи нема автоматски — само кога кликаш „Sync сега".
                </p>
            </div>

            <div class="rounded-md bg-sky-50 border border-sky-200 p-3 text-xs text-sky-900">
                <strong>Како работи:</strong> AI ја прочита страницата, ги извлекува понудите како draft огласи во „Мои огласи".
                Ти ги прегледуваш + објавуваш. <strong>Никогаш не публикува автоматски</strong> — секогаш чека одобрување.
            </div>

            <div class="flex items-center justify-between">
                <a href="{{ route('agency.import-sources') }}" class="text-sm text-slate-600 hover:underline">← Назад</a>
                <button type="submit" class="btn-primary">Додај извор</button>
            </div>
        </form>
    </div>
@endsection
