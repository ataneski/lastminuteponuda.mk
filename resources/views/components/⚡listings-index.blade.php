<?php

use App\Models\Listing;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'd')]
    public string $destination = '';

    #[Url(as: 'c')]
    public string $country = '';

    #[Url(as: 'b')]
    public string $board_type = '';

    #[Url(as: 't')]
    public string $transport = '';

    #[Url(as: 's')]
    public string $min_stars = '';

    #[Url(as: 'pmin')]
    public string $price_min = '';

    #[Url(as: 'pmax')]
    public string $price_max = '';

    #[Url(as: 'from')]
    public string $departure_from = '';

    #[Url(as: 'to')]
    public string $departure_to = '';

    public bool $show_filters = false;

    public function toggleFilters(): void
    {
        $this->show_filters = ! $this->show_filters;
    }

    public function clearFilters(): void
    {
        $this->reset([
            'search', 'destination', 'country', 'board_type',
            'transport', 'min_stars', 'price_min', 'price_max',
            'departure_from', 'departure_to',
        ]);
        $this->resetPage();
    }

    public function updating($name): void
    {
        if (! in_array($name, ['page', 'show_filters'])) {
            $this->resetPage();
        }
    }

    public function with(): array
    {
        $query = Listing::query()
            ->active()
            ->orderByRaw('(featured_until IS NOT NULL AND featured_until > ?) DESC', [now()])
            ->orderByDesc('created_at');

        if ($this->search !== '') {
            $query->where(function ($q) {
                $q->where('title', 'like', '%'.$this->search.'%')
                    ->orWhere('hotel_name', 'like', '%'.$this->search.'%')
                    ->orWhere('description', 'like', '%'.$this->search.'%');
            });
        }

        if ($this->destination !== '') {
            $query->where('destination', 'like', '%'.$this->destination.'%');
        }

        if ($this->country !== '') {
            $query->where('country', 'like', '%'.$this->country.'%');
        }

        if ($this->board_type !== '') {
            $query->where('board_type', $this->board_type);
        }

        if ($this->transport !== '') {
            $query->where('transport', $this->transport);
        }

        if ($this->min_stars !== '' && is_numeric($this->min_stars)) {
            $query->where('hotel_stars', '>=', (int) $this->min_stars);
        }

        if ($this->price_min !== '' && is_numeric($this->price_min)) {
            $query->where('price_per_person', '>=', (int) $this->price_min);
        }

        if ($this->price_max !== '' && is_numeric($this->price_max)) {
            $query->where('price_per_person', '<=', (int) $this->price_max);
        }

        if ($this->departure_from !== '') {
            $query->where('departure_date', '>=', $this->departure_from);
        }

        if ($this->departure_to !== '') {
            $query->where('departure_date', '<=', $this->departure_to);
        }

        return [
            'listings' => $query->paginate(12),
        ];
    }

    public function getActiveFilterCountProperty(): int
    {
        return collect([
            $this->country, $this->board_type, $this->transport,
            $this->min_stars, $this->price_min, $this->price_max,
            $this->departure_from, $this->departure_to,
        ])->filter(fn ($v) => $v !== '')->count();
    }
};
?>

<div class="space-y-6">
    <div class="grid gap-3 md:grid-cols-[1fr_1fr_auto]">
        <input wire:model.live.debounce.300ms="search" type="search" class="input"
            placeholder="Пребарај по наслов, хотел или опис…">
        <input wire:model.live.debounce.300ms="destination" type="search" class="input"
            placeholder="Филтрирај по дестинација…">
        <button type="button" wire:click="toggleFilters" class="btn-secondary whitespace-nowrap">
            Филтри
            @if ($this->activeFilterCount > 0)
                <span class="ml-1.5 inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-sky-600 px-1.5 text-xs font-semibold text-white">
                    {{ $this->activeFilterCount }}
                </span>
            @endif
        </button>
    </div>

    @if ($show_filters)
        <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
            <div class="grid gap-3 md:grid-cols-3">
                <div>
                    <label class="label">Држава</label>
                    <input wire:model.live.debounce.300ms="country" type="text" class="input" placeholder="пр. Грција">
                </div>
                <div>
                    <label class="label">Тип на пансион</label>
                    <select wire:model.live="board_type" class="input">
                        <option value="">Сите</option>
                        @foreach (\App\Models\Listing::BOARD_TYPES as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="label">Превоз</label>
                    <select wire:model.live="transport" class="input">
                        <option value="">Сите</option>
                        @foreach (\App\Models\Listing::TRANSPORTS as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="label">Мин. ѕвезди</label>
                    <select wire:model.live="min_stars" class="input">
                        <option value="">Без услов</option>
                        @foreach ([1, 2, 3, 4, 5] as $n)
                            <option value="{{ $n }}">{{ $n }}+ ★</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="label">Цена од</label>
                    <input wire:model.live.debounce.500ms="price_min" type="number" min="0" class="input" placeholder="EUR">
                </div>
                <div>
                    <label class="label">Цена до</label>
                    <input wire:model.live.debounce.500ms="price_max" type="number" min="0" class="input" placeholder="EUR">
                </div>
                <div>
                    <label class="label">Поаѓање од</label>
                    <input wire:model.live="departure_from" type="date" class="input">
                </div>
                <div>
                    <label class="label">Поаѓање до</label>
                    <input wire:model.live="departure_to" type="date" class="input">
                </div>
            </div>

            @if ($this->activeFilterCount > 0)
                <div class="mt-4 flex justify-end">
                    <button type="button" wire:click="clearFilters" class="text-sm text-slate-600 hover:text-slate-900">
                        Исчисти ги сите филтри
                    </button>
                </div>
            @endif
        </div>
    @endif

    @if ($listings->isEmpty())
        <div class="rounded-lg border-2 border-dashed border-slate-300 bg-white p-10 text-center">
            <p class="text-slate-600">Нема огласи кои одговараат на пребарувањето.</p>
        </div>
    @else
        <div class="text-sm text-slate-500">
            Прикажани {{ $listings->count() }} од {{ $listings->total() }} огласи
        </div>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($listings as $listing)
                <x-listing-card :listing="$listing" />
            @endforeach
        </div>

        <div>{{ $listings->links() }}</div>
    @endif
</div>
