<?php

use App\Mail\ListingPublishedMail;
use App\Models\Listing;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component
{
    use WithFileUploads;

    public ?Listing $listing = null;

    public function mount(?Listing $listing = null): void
    {
        if ($listing && $listing->exists) {
            $this->authorize('update', $listing);
            $this->listing = $listing;
            $this->agency_name = $listing->agency_name;
            $this->agency_contact = $listing->agency_contact;
            $this->title = $listing->title;
            $this->destination = $listing->destination;
            $this->country = $listing->country;
            $this->hotel_name = $listing->hotel_name;
            $this->hotel_stars = $listing->hotel_stars;
            $this->board_type = $listing->board_type;
            $this->transport = $listing->transport;
            $this->departure_date = $listing->departure_date->toDateString();
            $this->return_date = $listing->return_date->toDateString();
            $this->nights = $listing->nights;
            $this->price_per_person = $listing->price_per_person;
            $this->currency = $listing->currency;
            $this->available_seats = $listing->available_seats;
            $this->description = $listing->description;
            $this->image_url = $listing->image_url ?? '';
            $this->features = $listing->features ?? [];
            $this->expires_at = $listing->expires_at?->format('Y-m-d\TH:i') ?? '';
            $this->existing_images = $listing->images()
                ->orderBy('position')
                ->get(['id', 'url'])
                ->map(fn ($img) => ['id' => $img->id, 'url' => $img->url])
                ->all();

            return;
        }

        if ($user = auth()->user()) {
            $this->agency_name = $user->name;
            $this->agency_contact = $user->email;
        }
    }

    public function isEditing(): bool
    {
        return $this->listing !== null;
    }

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

    #[Validate('required|in:noBoard,breakfast,halfBoard,fullBoard,allInclusive,ultraAllInclusive')]
    public string $board_type = 'allInclusive';

    #[Validate('required|in:bus,plane,ownTransport,ferry')]
    public string $transport = 'plane';

    public string $departure_date = '';

    public string $return_date = '';

    public string $expires_at = '';

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

    /** @var array<int, \Livewire\Features\SupportFileUploads\TemporaryUploadedFile> */
    public array $image_files = [];

    /** @var array<int, array{id: int, url: string}> */
    public array $existing_images = [];

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
        $departureRule = ['required', 'date'];
        if (! $this->isEditing()) {
            $departureRule[] = 'after_or_equal:today';
        }

        return [
            'departure_date' => $departureRule,
            'return_date' => ['required', 'date', 'after:departure_date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:today'],
            'features' => ['array'],
            'features.*' => ['string', 'max:60'],
            'image_files' => ['array', 'max:10'],
            'image_files.*' => ['image', 'max:4096'],
        ];
    }

    public function removeExistingImage(int $imageId): void
    {
        if (! $this->isEditing()) {
            return;
        }
        $this->authorize('update', $this->listing);

        \App\Models\ListingImage::where('listing_id', $this->listing->id)
            ->where('id', $imageId)
            ->delete();

        $this->existing_images = array_values(array_filter(
            $this->existing_images,
            fn ($img) => $img['id'] !== $imageId
        ));
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
        if (! $this->isEditing()) {
            $key = 'create-listing:'.auth()->id();
            if (RateLimiter::tooManyAttempts($key, 5)) {
                $seconds = RateLimiter::availableIn($key);
                throw ValidationException::withMessages([
                    'agency_name' => "Пречекоривте го лимитот од 5 нови огласи на час. Обидете се повторно за {$seconds} секунди.",
                ]);
            }
            RateLimiter::hit($key, 3600);
        }

        $data = $this->validate();

        $uploadedFiles = $data['image_files'] ?? [];
        unset($data['image_files']);

        $data['expires_at'] = $this->expires_at !== '' ? $this->expires_at : null;

        if ($this->isEditing()) {
            $this->authorize('update', $this->listing);
            $this->listing->update($data);
            $listing = $this->listing;
            $message = 'Огласот е успешно ажуриран.';
        } else {
            $data['user_id'] = auth()->id();
            $listing = Listing::create($data);
            $message = 'Огласот е успешно објавен.';
        }

        if (! empty($uploadedFiles)) {
            $startingPosition = $listing->images()->max('position') ?? -1;
            foreach ($uploadedFiles as $i => $file) {
                $path = $file->store('listings', 'public');
                $listing->images()->create([
                    'url' => \Storage::url($path),
                    'position' => $startingPosition + 1 + $i,
                ]);
            }

            // Backfill primary image_url if none was set, for legacy/seed compat.
            if (empty($listing->image_url)) {
                $listing->update(['image_url' => $listing->images()->first()?->url]);
            }
        }

        // Email confirmation only on create, sent to the authenticated user.
        if (! $this->isEditing() && ($recipient = auth()->user()?->email)) {
            Mail::to($recipient)->queue(new ListingPublishedMail($listing));
        }

        session()->flash('status', $message);

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
                    <label class="label">Слики (повеќе)</label>
                    <input wire:model="image_files" type="file" accept="image/*" multiple class="input">
                    <p class="mt-1 text-xs text-slate-500">JPG/PNG/WEBP, до 4 MB по слика. Максимум 10.</p>
                    @error('image_files.*') <p class="error">{{ $message }}</p> @enderror
                    @error('image_files') <p class="error">{{ $message }}</p> @enderror
                    <div wire:loading wire:target="image_files" class="mt-1 text-xs text-slate-500">
                        Се качуваат сликите…
                    </div>

                    @if (count($existing_images) > 0)
                        <div class="mt-3">
                            <p class="text-xs font-medium text-slate-700 mb-1">Постоечки слики:</p>
                            <div class="grid grid-cols-3 gap-2">
                                @foreach ($existing_images as $img)
                                    <div class="relative">
                                        <img src="{{ $img['url'] }}" alt="" class="h-24 w-full rounded-md object-cover">
                                        <button type="button"
                                            wire:click="removeExistingImage({{ $img['id'] }})"
                                            class="absolute top-1 right-1 rounded-full bg-red-600 px-2 py-0.5 text-xs text-white hover:bg-red-700"
                                            title="Отстрани">×</button>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if (count($image_files) > 0)
                        <div class="mt-3">
                            <p class="text-xs font-medium text-slate-700 mb-1">Нови слики (преглед):</p>
                            <div class="grid grid-cols-3 gap-2">
                                @foreach ($image_files as $file)
                                    <img src="{{ $file->temporaryUrl() }}" alt="Преглед"
                                        class="h-24 w-full rounded-md object-cover">
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
                <div>
                    <label class="label">…или URL на главна слика</label>
                    <input wire:model.blur="image_url" type="url" class="input" placeholder="https://…">
                    <p class="mt-1 text-xs text-slate-500">Ако веќе имаш слика онлајн.</p>
                    @error('image_url') <p class="error">{{ $message }}</p> @enderror
                </div>
            </div>
        </section>

        <div class="flex items-center justify-end gap-3">
            <button type="button" class="btn-secondary"
                wire:click="$set('features', [])">Исчисти карактеристики</button>
            <button type="submit" class="btn-primary" wire:loading.attr="disabled" wire:target="save">
                <span wire:loading.remove wire:target="save">
                    {{ $this->isEditing() ? 'Зачувај промени' : 'Објави оглас' }}
                </span>
                <span wire:loading wire:target="save">Се зачувува…</span>
            </button>
        </div>
    </form>
</div>
