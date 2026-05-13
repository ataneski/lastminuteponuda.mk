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
            <span class="text-sm">План е достапен</span>
        </label>
    </div>
</div>

<div class="mt-8">
    <h3 class="text-sm font-semibold text-slate-900 mb-3">Функционалности</h3>
    <div class="space-y-4">
        @foreach (\App\Models\Tier::GROUP_LABELS as $groupKey => $groupLabel)
            @php $itemsInGroup = collect($featureMeta)->filter(fn ($m) => $m['group'] === $groupKey); @endphp
            @if ($itemsInGroup->isEmpty()) @continue @endif

            <div class="rounded-lg border border-slate-200 bg-slate-50/50 p-4">
                <h4 class="text-xs font-bold uppercase tracking-wide text-slate-500 mb-3">{{ $groupLabel }}</h4>
                <div class="space-y-3">
                    @foreach ($itemsInGroup as $key => $meta)
                        @php $current = old("features.{$key}", $tier?->feature($key)); @endphp
                        <div class="flex items-center justify-between gap-3">
                            <div class="flex-1 min-w-0">
                                <label for="feat-{{ $key }}" class="text-sm font-medium text-slate-800 block">
                                    {{ $meta['label'] }}
                                </label>
                                @if (! empty($meta['help']))
                                    <p class="text-xs text-slate-500 mt-0.5">{{ $meta['help'] }}</p>
                                @endif
                            </div>
                            <div class="w-32 shrink-0">
                                @if ($meta['type'] === 'bool')
                                    {{-- Custom toggle switch --}}
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input id="feat-{{ $key }}" name="features[{{ $key }}]"
                                            type="checkbox" value="1" class="sr-only peer"
                                            {{ $current ? 'checked' : '' }}>
                                        <div class="w-11 h-6 bg-slate-300 rounded-full peer
                                            peer-checked:bg-emerald-500
                                            after:content-[''] after:absolute after:top-0.5 after:left-0.5
                                            after:bg-white after:rounded-full after:h-5 after:w-5 after:transition
                                            peer-checked:after:translate-x-5"></div>
                                    </label>
                                @elseif ($meta['type'] === 'int')
                                    <input id="feat-{{ $key }}" name="features[{{ $key }}]" type="number" min="0"
                                        class="input text-sm tabular-nums" value="{{ $current ?? 0 }}">
                                @elseif ($meta['type'] === 'nullable_int')
                                    <input id="feat-{{ $key }}" name="features[{{ $key }}]" type="number" min="0"
                                        class="input text-sm tabular-nums" placeholder="∞" value="{{ $current }}">
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</div>
