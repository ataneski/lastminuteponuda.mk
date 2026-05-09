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

    public function updating($name): void
    {
        if (in_array($name, ['search', 'destination'])) {
            $this->resetPage();
        }
    }

    public function with(): array
    {
        $query = Listing::query()->orderByDesc('created_at');

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

        return [
            'listings' => $query->paginate(12),
        ];
    }
};
?>

<div class="space-y-6">
    <div class="grid gap-3 md:grid-cols-2">
        <input wire:model.live.debounce.300ms="search" type="search" class="input"
            placeholder="Пребарај по наслов, хотел или опис…">
        <input wire:model.live.debounce.300ms="destination" type="search" class="input"
            placeholder="Филтрирај по дестинација…">
    </div>

    @if ($listings->isEmpty())
        <div class="rounded-lg border-2 border-dashed border-slate-300 bg-white p-10 text-center">
            <p class="text-slate-600">Нема огласи кои одговараат на пребарувањето.</p>
        </div>
    @else
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($listings as $listing)
                <x-listing-card :listing="$listing" />
            @endforeach
        </div>

        <div>{{ $listings->links() }}</div>
    @endif
</div>
