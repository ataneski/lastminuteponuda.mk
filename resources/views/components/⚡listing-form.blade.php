<?php

use App\Models\Listing;
use Livewire\Attributes\Validate;
use Livewire\Component;

new class extends Component
{
    #[Validate('required|string|max:120')]
    public string $agency_name = '';

    #[Validate('required|string|max:120')]
    public string $agency_contact = '';

    #[Validate('required|string|max:160')]
    public string $title = '';

    #[Validate('required|string|max:120')]
    public string $destination = '';

    #[Validate('required|string|max:120')]
    public string $country = '';

    #[Validate('required|string|max:160')]
    public string $hotel_name = '';

    #[Validate('required|integer|min:1|max:5')]
    public int $hotel_stars = 4;

    #[Validate('required|string')]
    public string $board_type = 'allInclusive';

    #[Validate('required|string')]
    public string $transport = 'plane';

    #[Validate('required|date|after_or_equal:today')]
    public string $departure_date = '';

    #[Validate('required|date|after:departure_date')]
    public string $return_date = '';

    #[Validate('required|integer|min:1|max:60')]
    public int $nights = 7;

    #[Validate('required|integer|min:1|max:1000000')]
    public int $price_per_person = 0;

    #[Validate('required|in:EUR,MKD,USD')]
    public string $currency = 'EUR';

    #[Validate('required|integer|min:1|max:1000')]
    public int $available_seats = 2;

    #[Validate('required|string|min:20|max:5000')]
    public string $description = '';

    #[Validate('nullable|url|max:500')]
    public string $image_url = '';

    /** @var array<int, string> */
    public array $features = [];

    public string $custom_feature = '';

    public array $commonFeatures = [
        'Базен',
        'Плажа на 5 мин',
        'Wi-Fi',
        'Климатизација',
        'Анимација за деца',
        'Спа центар',
        'Паркинг',
        'Превоз од аеродром',
    ];

    public function rules(): array
    {
        return [
            'board_type' => ['required', 'in:'.implode(',', array_keys(Listing::BOARD_TYPES))],
            'transport' => ['required', 'in:'.implode(',', array_keys(Listing::TRANSPORTS))],
            'features' => ['array'],
            'features.*' => ['string', 'max:60'],
        ];
    }

    public function toggleFeature(string $feature): void
    {
        if (in_array($feature, $this->features, true)) {
            $this->features = array_values(array_filter($this->features, fn ($f) => $f !== $feature));
        } else {
            $this->features[] = $feature;
        }
    }

    public function addCustomFeature(): void
    {
        $value = trim($this->custom_feature);
        if ($value === '' || in_array($value, $this->features, true)) {
            $this->custom_feature = '';

            return;
        }
        $this->features[] = $value;
        $this->custom_feature = '';
    }

    public function save()
    {
        $data = $this->validate();

        $listing = Listing::create($data);

        session()->flash('status', 'Огласот е успешно објавен.');

        return redirect()->route('listings.show', $listing);
    }
};
?>

<div class="space-y-8">
    <form wire:submit="save" class="space-y-8">
        {{-- Агенција --}}
        <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="mb-4 text-lg font-semibold text-slate-900">Агенција</h2>
            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="label">Име на агенција <span class="text-red-500">*</span></label>
                    <input wire:model.blur="agency_name" type="text" class="input" placeholder="пр. Балкан Травел">
                    @error('agency_name') <p class="error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label">Контакт (телефон или e-mail) <span class="text-red-500">*</span></label>
                    <input wire:model.blur="agency_contact" type="text" class="input" placeholder="пр. +389 70 123 456">
                    @error('agency_contact') <p class="error">{{ $message }}</p> @enderror
                </div>
            </div>
        </section>

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

        {{-- Опис и слика --}}
        <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="mb-4 text-lg font-semibold text-slate-900">Опис и слика</h2>
            <div class="space-y-4">
                <div>
                    <label class="label">Опис <span class="text-red-500">*</span></label>
                    <textarea wire:model.blur="description" rows="5" class="input"
                        placeholder="Накратко за понудата, локацијата, услугата…"></textarea>
                    @error('description') <p class="error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label">URL на слика (опционално)</label>
                    <input wire:model.blur="image_url" type="url" class="input" placeholder="https://…">
                    @error('image_url') <p class="error">{{ $message }}</p> @enderror
                </div>
            </div>
        </section>

        <div class="flex items-center justify-end gap-3">
            <button type="button" class="btn-secondary"
                wire:click="$set('features', [])">Исчисти карактеристики</button>
            <button type="submit" class="btn-primary" wire:loading.attr="disabled" wire:target="save">
                <span wire:loading.remove wire:target="save">Објави оглас</span>
                <span wire:loading wire:target="save">Се зачувува…</span>
            </button>
        </div>
    </form>
</div>
