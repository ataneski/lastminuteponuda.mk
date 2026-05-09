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
            $user = auth()->user();
            if (! $user->canCreateListing()) {
                throw ValidationException::withMessages([
                    'agency_name' => 'Free tier е лимитиран на '.\App\Models\User::FREE_TIER_ACTIVE_LIMIT.
                        ' активни огласи. Надградете на Pro за неограничено.',
                ]);
            }

            $key = 'create-listing:'.$user->id;
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

        @include('components.listing-fields')

        {{-- Слики (специфично за оваа форма) --}}
        <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="mb-4 text-lg font-semibold text-slate-900">Слика</h2>
            <div class="space-y-4">
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
