@php
    $active = $active ?? 'index';
    $tabs = [
        'index' => ['Преглед', route('admin.index')],
        'agencies' => ['Агенции', route('admin.agencies')],
        'listings' => ['Огласи', route('admin.listings')],
        'tiers' => ['Tiers', route('admin.tiers')],
    ];
@endphp
<nav class="mb-6 flex gap-1 border-b border-slate-200">
    @foreach ($tabs as $key => [$label, $url])
        <a href="{{ $url }}"
            class="px-3 py-2 text-sm font-medium border-b-2 -mb-px
                {{ $active === $key ? 'border-sky-600 text-sky-700' : 'border-transparent text-slate-600 hover:text-slate-900' }}">
            {{ $label }}
        </a>
    @endforeach
</nav>
