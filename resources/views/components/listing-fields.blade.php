{{--
    Shared listing fields partial — used by:
    - <livewire:listing-form /> (manual create/edit)
    - <livewire:ai-listing-wizard /> (AI-assisted create, step 3 review)

    Expects the enclosing Livewire component to expose:
      string $title, $destination, $country, $hotel_name
      int    $hotel_stars
      string $board_type, $transport
      string $departure_date, $return_date, $expires_at
      int    $nights, $price_per_person, $available_seats
      string $currency, $description
      array  $features, $commonFeatures
      string $custom_feature
    + methods: toggleFeature(feature), addCustomFeature()
--}}

{{-- Понуда --}}
<section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
    <h2 class="mb-4 text-lg font-semibold text-slate-900">Понуда</h2>
    <div class="grid gap-4 md:grid-cols-2">
        <div class="md:col-span-2">
            <label class="label">Наслов на оглас <span class="text-red-500">*</span></label>
            <input wire:model.blur="title" type="text" class="input" placeholder="пр. 7 ноќи Анталија — All Inclusive">
            @error('title') <p class="error">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="label">Дестинација <span class="text-red-500">*</span></label>
            <input wire:model.blur="destination" type="text" class="input" placeholder="пр. Анталија">
            @error('destination') <p class="error">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="label">Држава <span class="text-red-500">*</span></label>
            <input wire:model.blur="country" type="text" class="input" placeholder="пр. Турција">
            @error('country') <p class="error">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="label">Хотел <span class="text-red-500">*</span></label>
            <input wire:model.blur="hotel_name" type="text" class="input" placeholder="пр. Royal Seginus">
            @error('hotel_name') <p class="error">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="label">Категорија (ѕвезди) <span class="text-red-500">*</span></label>
            <select wire:model="hotel_stars" class="input">
                @foreach ([1, 2, 3, 4, 5] as $n)
                    <option value="{{ $n }}">{{ $n }} ★</option>
                @endforeach
            </select>
            @error('hotel_stars') <p class="error">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="label">Тип на пансион <span class="text-red-500">*</span></label>
            <select wire:model="board_type" class="input">
                @foreach (\App\Models\Listing::BOARD_TYPES as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </select>
            @error('board_type') <p class="error">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="label">Превоз <span class="text-red-500">*</span></label>
            <select wire:model="transport" class="input">
                @foreach (\App\Models\Listing::TRANSPORTS as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </select>
            @error('transport') <p class="error">{{ $message }}</p> @enderror
        </div>
    </div>
</section>

{{-- Термин и цена --}}
<section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
    <h2 class="mb-4 text-lg font-semibold text-slate-900">Термин и цена</h2>
    <div class="grid gap-4 md:grid-cols-3">
        <div>
            <label class="label">Поаѓање <span class="text-red-500">*</span></label>
            <input wire:model.blur="departure_date" type="date" class="input">
            @error('departure_date') <p class="error">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="label">Враќање <span class="text-red-500">*</span></label>
            <input wire:model.blur="return_date" type="date" class="input">
            @error('return_date') <p class="error">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="label">Ноќевања <span class="text-red-500">*</span></label>
            <input wire:model.blur="nights" type="number" min="1" class="input">
            @error('nights') <p class="error">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="label">Цена по лице <span class="text-red-500">*</span></label>
            <input wire:model.blur="price_per_person" type="number" min="1" class="input" placeholder="пр. 499">
            @error('price_per_person') <p class="error">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="label">Валута <span class="text-red-500">*</span></label>
            <select wire:model="currency" class="input">
                <option value="EUR">EUR</option>
                <option value="MKD">MKD</option>
                <option value="USD">USD</option>
            </select>
            @error('currency') <p class="error">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="label">Слободни места <span class="text-red-500">*</span></label>
            <input wire:model.blur="available_seats" type="number" min="1" class="input">
            @error('available_seats') <p class="error">{{ $message }}</p> @enderror
        </div>
        <div class="md:col-span-3">
            <label class="label">Истекува</label>
            <input wire:model.blur="expires_at" type="datetime-local" class="input">
            <p class="mt-1 text-xs text-slate-500">
                Оставете празно за оглас без рок. Препорачано: ден на враќање или порано.
                Истечените огласи се сокриваат од јавноста.
            </p>
            @error('expires_at') <p class="error">{{ $message }}</p> @enderror
        </div>
    </div>
</section>

{{-- Карактеристики --}}
<section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
    <h2 class="mb-2 text-lg font-semibold text-slate-900">Карактеристики</h2>
    <p class="text-sm text-slate-500 mb-3">Изберете ги карактеристиките кои важат за оваа понуда.</p>
    <div class="flex flex-wrap gap-2">
        @foreach ($commonFeatures as $feature)
            @php $active = in_array($feature, $features, true); @endphp
            <button type="button" wire:click="toggleFeature('{{ $feature }}')"
                class="{{ $active
                    ? 'rounded-full border border-sky-600 bg-sky-600 px-3 py-1.5 text-sm text-white'
                    : 'rounded-full border border-slate-300 bg-white px-3 py-1.5 text-sm text-slate-700 hover:border-sky-500' }}">
                {{ $active ? '✓ ' : '' }}{{ $feature }}
            </button>
        @endforeach
    </div>
    <div class="mt-3 flex gap-2">
        <input wire:model="custom_feature" wire:keydown.enter.prevent="addCustomFeature" type="text"
            class="input" placeholder="Додај друга карактеристика…">
        <button type="button" wire:click="addCustomFeature" class="btn-secondary">Додај</button>
    </div>
    @if (count($features))
        <div class="mt-3 flex flex-wrap gap-2">
            @foreach ($features as $f)
                <span class="badge">
                    {{ $f }}
                    <button type="button" wire:click="toggleFeature('{{ $f }}')"
                        class="ml-1.5 text-sky-700 hover:text-sky-900" aria-label="Отстрани">×</button>
                </span>
            @endforeach
        </div>
    @endif
</section>

{{-- Опис --}}
<section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
    <h2 class="mb-4 text-lg font-semibold text-slate-900">Опис</h2>
    <div>
        <label class="label">Опис <span class="text-red-500">*</span></label>
        <textarea wire:model.blur="description" rows="5" class="input"
            placeholder="Накратко за понудата, локацијата, услугата…"></textarea>
        @error('description') <p class="error">{{ $message }}</p> @enderror
    </div>
</section>
