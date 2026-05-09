<?php

use App\Jobs\ProcessListingDraftJob;
use App\Models\Listing;
use App\Services\ImageProcessor;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * 3-step wizard for the AI-assisted listing flow:
 *   Step 1: Upload 3–15 raw photos (drag/drop)
 *   Step 2: Fill the CPT form (price, dates, board, transport, seats,
 *           hotel, destination, country, stars)
 *   Step 3: Wait for AI (poll every 3s) → review the suggested title +
 *           AI-ordered gallery → click "Објави" to publish
 *
 * On submit at end of step 2, we create a draft Listing (published_at
 * is null), persist & process images via ImageProcessor, and dispatch
 * ProcessListingDraftJob. The wizard advances to step 3 immediately;
 * the agency sees a spinner until ai_generated_at is stamped.
 */
new class extends Component
{
    use WithFileUploads;

    // -- wizard state --
    public int $step = 1;

    public ?int $draftId = null;

    public bool $synced = false;

    // -- Step 1: photos --
    /** @var array */
    public array $image_files = [];

    // -- Step 2: CPT form (mirrors Listing fields the listing-fields partial expects) --
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

    #[Validate('nullable|string|max:5000')]
    public string $description = '';

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

    public function mount(): void
    {
        if ($user = auth()->user()) {
            // Defaults for hidden form fields shared with manual flow.
            // The listing's agency_name/contact come from the user profile.
        }
    }

    public function rules(): array
    {
        return [
            'departure_date' => ['required', 'date', 'after_or_equal:today'],
            'return_date' => ['required', 'date', 'after:departure_date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:today'],
            'image_files' => ['array', 'min:3', 'max:15'],
            'image_files.*' => ['image', 'max:8192'],
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

    public function nextFromUpload(): void
    {
        $this->validateOnly('image_files');
        $this->step = 2;
    }

    public function backToUpload(): void
    {
        $this->step = 1;
    }

    public function createDraft(ImageProcessor $processor)
    {
        if (! auth()->user()->canCreateListing()) {
            throw ValidationException::withMessages([
                'title' => 'Free tier е лимитиран на '.\App\Models\User::FREE_TIER_ACTIVE_LIMIT.
                    ' активни огласи. Надградете на Pro.',
            ]);
        }

        $data = $this->validate();
        $uploadedFiles = $data['image_files'] ?? [];
        unset($data['image_files']);

        $user = auth()->user();
        $data['user_id'] = $user->id;
        $data['agency_name'] = $user->display_name ?: $user->name;
        $data['agency_contact'] = $user->phone ?: $user->email;
        $data['expires_at'] = $this->expires_at !== '' ? $this->expires_at : null;
        $data['published_at'] = null; // draft

        $listing = Listing::create($data);
        $this->draftId = $listing->id;

        // Process every uploaded photo through Intervention (EXIF
        // rotation + resize). This is synchronous because Livewire
        // already streamed the bytes to the public/livewire-tmp/ disk;
        // image processing on ≤15 mid-size JPEGs is fast enough to
        // not warrant a separate job.
        foreach ($uploadedFiles as $i => $file) {
            $bytes = file_get_contents($file->getRealPath());
            $result = $processor->processBytes($bytes);
            $listing->images()->create([
                'url' => $result['url'],
                'position' => $i,
            ]);
        }

        // Set primary so the wizard can show a preview before AI runs.
        $first = $listing->images()->orderBy('position')->first();
        if ($first) {
            $listing->update(['image_url' => $first->url]);
        }

        ProcessListingDraftJob::dispatch($listing->id);

        $this->step = 3;
    }

    /**
     * Called by wire:poll.3s on step 3. Once the AI job has stamped
     * ai_generated_at, copy the AI-derived title back into the wizard
     * state so the agency sees and can edit it.
     */
    public function syncDraftFromAi(): void
    {
        if ($this->synced || ! $this->draftId) {
            return;
        }
        $listing = Listing::find($this->draftId);
        if (! $listing || $listing->ai_generated_at === null) {
            return;
        }

        $this->title = $listing->title ?: $this->title;
        $this->synced = true;
    }

    public function publish()
    {
        $listing = Listing::findOrFail($this->draftId);
        abort_unless($listing->user_id === auth()->id(), 403);

        // Save the latest wizard state (title may have been edited in
        // step 3 after AI ran). Image positions are already in DB.
        $listing->update([
            'title' => $this->title,
            'destination' => $this->destination,
            'country' => $this->country,
            'hotel_name' => $this->hotel_name,
            'hotel_stars' => $this->hotel_stars,
            'board_type' => $this->board_type,
            'transport' => $this->transport,
            'departure_date' => $this->departure_date,
            'return_date' => $this->return_date,
            'expires_at' => $this->expires_at !== '' ? $this->expires_at : null,
            'nights' => $this->nights,
            'price_per_person' => $this->price_per_person,
            'currency' => $this->currency,
            'available_seats' => $this->available_seats,
            'description' => $this->description,
            'features' => $this->features,
        ]);

        $listing->publish();

        session()->flash('status', 'Огласот е објавен.');

        return redirect()->route('listings.show', $listing);
    }

    public function getDraftProperty(): ?Listing
    {
        return $this->draftId
            ? Listing::with('images')->find($this->draftId)
            : null;
    }
};
?>

<div class="space-y-6">
    {{-- Step indicator --}}
    <ol class="flex items-center gap-2 text-sm">
        @foreach ([1 => 'Слики', 2 => 'Податоци', 3 => 'Преглед'] as $n => $label)
            @php $state = $step === $n ? 'active' : ($step > $n ? 'done' : 'todo'); @endphp
            <li class="flex items-center gap-2">
                <span class="{{ $state === 'active'
                    ? 'bg-sky-600 text-white'
                    : ($state === 'done' ? 'bg-emerald-500 text-white' : 'bg-slate-200 text-slate-600') }} h-7 w-7 rounded-full flex items-center justify-center font-semibold">
                    {{ $state === 'done' ? '✓' : $n }}
                </span>
                <span class="{{ $state === 'active' ? 'font-semibold text-slate-900' : 'text-slate-500' }}">{{ $label }}</span>
            </li>
            @if ($n < 3)
                <li class="flex-1 h-px bg-slate-200"></li>
            @endif
        @endforeach
    </ol>

    @if ($step === 1)
        {{-- Step 1: photo upload --}}
        <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-900">Прикачете слики од хотелот, собата, локацијата</h2>
            <p class="mt-1 text-sm text-slate-600">
                Прикачете 3–15 слики (JPG/PNG). Ние ќе ги стандардизираме за галерија и
                автоматски ќе го одредиме најдобриот редослед.
            </p>

            <div class="mt-4">
                <input wire:model="image_files" type="file" accept="image/*" multiple class="input">
                @error('image_files') <p class="error">{{ $message }}</p> @enderror
                @error('image_files.*') <p class="error">{{ $message }}</p> @enderror
                <div wire:loading wire:target="image_files" class="mt-2 text-xs text-slate-500">
                    Се качуваат сликите…
                </div>

                @if (count($image_files) > 0)
                    <div class="mt-4">
                        <p class="text-xs font-medium text-slate-700 mb-2">Прикачени ({{ count($image_files) }}):</p>
                        <div class="grid grid-cols-3 md:grid-cols-5 gap-2">
                            @foreach ($image_files as $file)
                                <img src="{{ $file->temporaryUrl() }}" alt=""
                                    class="h-24 w-full rounded-md object-cover border border-slate-200">
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            <div class="mt-6 flex justify-end">
                <button type="button" wire:click="nextFromUpload" class="btn-primary"
                    @disabled(count($image_files) < 3)>
                    Понатаму →
                </button>
            </div>
        </section>
    @endif

    @if ($step === 2)
        {{-- Step 2: CPT form (uses shared partial) --}}
        <form wire:submit="createDraft" class="space-y-6">
            @include('components.listing-fields')

            <div class="flex items-center justify-between gap-3">
                <button type="button" wire:click="backToUpload" class="btn-secondary">← Назад</button>
                <button type="submit" class="btn-primary" wire:loading.attr="disabled" wire:target="createDraft">
                    <span wire:loading.remove wire:target="createDraft">Создај draft и стартувај AI →</span>
                    <span wire:loading wire:target="createDraft">Се обработува…</span>
                </button>
            </div>
        </form>
    @endif

    @if ($step === 3 && $this->draft)
        {{-- Step 3: AI processing + review --}}
        @php $draft = $this->draft; @endphp

        <div wire:poll.3s="syncDraftFromAi">
            @if ($draft->ai_generated_at === null)
                <section class="rounded-lg border-2 border-dashed border-sky-300 bg-sky-50 p-10 text-center">
                    <div class="mx-auto h-10 w-10 animate-spin rounded-full border-4 border-sky-300 border-t-sky-700"></div>
                    <h2 class="mt-4 text-lg font-semibold text-sky-900">AI ги обработува сликите…</h2>
                    <p class="mt-1 text-sm text-sky-800">
                        Ова обично трае 5–15 секунди. Страницата ќе се освежи автоматски.
                    </p>
                </section>
            @else
                <section class="rounded-lg border border-emerald-300 bg-emerald-50 p-4">
                    <p class="text-sm text-emerald-900">
                        ✓ AI ги среди сликите и предложи наслов. Прегледајте, уредете ако треба, потоа објавете.
                    </p>
                </section>

                {{-- Preview of the gallery in AI-suggested order --}}
                <section class="mt-4 rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-lg font-semibold text-slate-900">Галерија (AI редослед)</h2>
                    <div class="mt-3 grid grid-cols-3 md:grid-cols-5 gap-2">
                        @foreach ($draft->images()->orderBy('position')->get() as $i => $img)
                            <div class="relative">
                                <img src="{{ $img->url }}" alt=""
                                    class="h-24 w-full rounded-md object-cover border border-slate-200">
                                @if ($i === 0)
                                    <span class="absolute top-1 left-1 rounded-full bg-amber-500 px-1.5 py-0.5 text-[10px] font-semibold text-white">
                                        HERO
                                    </span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </section>

                {{-- Editable fields (same partial) --}}
                <div class="mt-4 space-y-4">
                    @include('components.listing-fields')
                </div>

                <div class="mt-4 flex items-center justify-between gap-3">
                    <a href="{{ route('listings.show', $draft) }}" target="_blank"
                        class="text-sm text-sky-700 hover:underline">Прегледај како што ќе изгледа →</a>
                    <button type="button" wire:click="publish" class="btn-primary">
                        Објави оглас
                    </button>
                </div>
            @endif
        </div>
    @endif
</div>
