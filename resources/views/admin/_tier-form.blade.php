{{-- Shared fields for tier create/edit. Parent supplies $tier (or null) and $featureMeta. --}}
@php $tier = $tier ?? null; @endphp

<div class="grid gap-4 md:grid-cols-2">
    <div>
        <label class="label">Key (URL-slug) <span class="text-red-500">*</span></label>
        <input type="text" name="key" class="input" required pattern="[a-z0-9_-]+"
            value="{{ old('key', $tier?->key) }}"
            @if ($tier) readonly @endif>
        <p class="mt-1 text-xs text-slate-500">Само мали букви, броеви, подцрта, цртичка. Не може да се менува по создавање.</p>
        @error('key') <p class="error">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="label">Име <span class="text-red-500">*</span></label>
        <input type="text" name="name" class="input" required
            value="{{ old('name', $tier?->name) }}">
        @error('name') <p class="error">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="label">Месечна цена</label>
        <input type="number" name="monthly_price" min="0" class="input"
            value="{{ old('monthly_price', $tier?->monthly_price ?? 0) }}">
        @error('monthly_price') <p class="error">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="label">Валута</label>
        <select name="currency" class="input">
            @foreach (['MKD', 'EUR', 'USD'] as $c)
                <option value="{{ $c }}" {{ old('currency', $tier?->currency ?? 'MKD') === $c ? 'selected' : '' }}>{{ $c }}</option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="label">Sort order</label>
        <input type="number" name="sort_order" class="input" value="{{ old('sort_order', $tier?->sort_order ?? 0) }}">
    </div>
    <div>
        <label class="label">Активен</label>
        <label class="inline-flex items-center gap-2 mt-2">
            <input type="checkbox" name="active" value="1"
                {{ old('active', $tier?->active ?? true) ? 'checked' : '' }}>
            <span class="text-sm">Tier е достапен</span>
        </label>
    </div>
</div>

<div class="mt-6 rounded-lg border border-slate-200 bg-slate-50 p-4">
    <h3 class="text-sm font-semibold text-slate-900 mb-3">Функционалности</h3>
    <div class="space-y-3">
        @foreach ($featureMeta as $key => $meta)
            @php
                $current = old("features.{$key}", $tier?->feature($key));
            @endphp
            <div class="flex items-center justify-between gap-3">
                <label for="feat-{{ $key }}" class="text-sm text-slate-700 flex-1">
                    {{ $meta['label'] }}
                    <code class="text-xs text-slate-400">{{ $key }}</code>
                </label>
                <div class="w-40">
                    @if ($meta['type'] === 'bool')
                        <select id="feat-{{ $key }}" name="features[{{ $key }}]" class="input text-sm">
                            <option value="0" {{ ! $current ? 'selected' : '' }}>Off</option>
                            <option value="1" {{ $current ? 'selected' : '' }}>On</option>
                        </select>
                    @elseif ($meta['type'] === 'int')
                        <input id="feat-{{ $key }}" name="features[{{ $key }}]" type="number" min="0"
                            class="input text-sm" value="{{ $current ?? 0 }}">
                    @elseif ($meta['type'] === 'nullable_int')
                        <input id="feat-{{ $key }}" name="features[{{ $key }}]" type="number" min="0"
                            class="input text-sm" placeholder="∞" value="{{ $current }}">
                    @endif
                </div>
            </div>
        @endforeach
    </div>
    <p class="mt-3 text-xs text-slate-500">
        За поле „Максимум активни огласи", оставете го празно за неограничено (∞).
    </p>
</div>
