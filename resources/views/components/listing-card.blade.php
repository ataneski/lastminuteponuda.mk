@props(['listing'])

<a href="{{ route('listings.show', $listing) }}"
    class="block rounded-lg border {{ $listing->isFeatured() ? 'border-amber-300 ring-1 ring-amber-200' : 'border-slate-200' }} bg-white shadow-sm hover:shadow-md hover:border-sky-300 transition overflow-hidden relative">
    @if ($listing->isFeatured())
        <span class="absolute top-2 left-2 z-10 inline-flex items-center gap-1 rounded-full bg-amber-500 px-2.5 py-0.5 text-xs font-semibold text-white shadow">
            ★ Препорачано
        </span>
    @endif
    @if ($listing->image_url)
        <img src="{{ $listing->image_url }}" alt="{{ $listing->title }}" class="h-44 w-full object-cover">
    @else
        <div class="h-44 w-full bg-gradient-to-br from-sky-100 to-sky-50 flex items-center justify-center text-sky-700 font-semibold">
            {{ $listing->destination }}
        </div>
    @endif
    <div class="p-4">
        <div class="flex items-start justify-between gap-3">
            <h3 class="text-base font-semibold text-slate-900 line-clamp-2">{{ $listing->title }}</h3>
            <span class="shrink-0 text-amber-500 text-sm">{{ str_repeat('★', $listing->hotel_stars) }}</span>
        </div>
        <p class="mt-1 text-sm text-slate-600">
            {{ $listing->destination }}, {{ $listing->country }} · {{ $listing->hotel_name }}
        </p>
        <div class="mt-3 flex flex-wrap gap-1.5">
            <span class="badge">{{ $listing->board_label }}</span>
            <span class="badge">{{ $listing->transport_label }}</span>
            <span class="badge">{{ $listing->nights }} ноќи</span>
        </div>
        @if ($listing->user)
            <div class="mt-3 flex items-center gap-2 text-xs text-slate-500">
                @if ($listing->user->logo_url)
                    <img src="{{ $listing->user->logo_url }}" alt="" class="h-5 w-5 rounded object-cover">
                @endif
                <span>{{ $listing->user->brand_name }}</span>
            </div>
        @endif
        <div class="mt-4 flex items-end justify-between">
            <div class="text-xs text-slate-500">
                {{ $listing->departure_date->format('d.m.Y') }} — {{ $listing->return_date->format('d.m.Y') }}
            </div>
            <div class="text-right">
                <div class="text-lg font-bold text-sky-700">{{ $listing->formatted_price }}</div>
                <div class="text-xs text-slate-500">по лице</div>
            </div>
        </div>
    </div>
</a>
