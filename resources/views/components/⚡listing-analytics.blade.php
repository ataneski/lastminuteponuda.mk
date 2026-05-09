<?php

use App\Models\Listing;
use Livewire\Attributes\Url;
use Livewire\Component;

new class extends Component
{
    public Listing $listing;

    #[Url(as: 'r')]
    public string $range = '30';

    public array $allowedRanges = ['7', '30', '90'];

    public function mount(Listing $listing): void
    {
        $this->authorize('update', $listing);
        $this->listing = $listing;
    }

    public function setRange(string $range): void
    {
        if (in_array($range, $this->allowedRanges, true)) {
            $this->range = $range;
        }
    }

    public function with(): array
    {
        $days = (int) $this->range;
        $start = now()->startOfDay()->subDays($days - 1);
        $end = now()->endOfDay();

        // Pull existing day rows in range as [Y-m-d => count]
        $byDay = $this->listing->dailyViews()
            ->whereBetween('day', [$start->toDateString(), $end->toDateString()])
            ->pluck('count', 'day')
            ->mapWithKeys(fn ($c, $d) => [(string) $d => (int) $c])
            ->all();

        // Build a continuous series with zeros for missing days
        $series = [];
        for ($i = 0; $i < $days; $i++) {
            $d = $start->copy()->addDays($i)->toDateString();
            $series[] = [
                'day' => $d,
                'count' => $byDay[$d] ?? 0,
            ];
        }

        $totalViews = array_sum(array_column($series, 'count'));
        $maxDay = max(1, max(array_column($series, 'count')));

        $inquiries = $this->listing->inquiries()
            ->where('created_at', '>=', $start)
            ->orderByDesc('created_at')
            ->get();

        return [
            'series' => $series,
            'totalViews' => $totalViews,
            'maxDay' => $maxDay,
            'inquiriesInRange' => $inquiries->count(),
            'inquiriesList' => $inquiries->take(20),
            'conversion' => $totalViews > 0
                ? round(($inquiries->count() / $totalViews) * 100, 2)
                : 0,
            'allTimeViews' => $this->listing->views_count,
            'allTimeInquiries' => $this->listing->inquiries()->count(),
        ];
    }
};
?>

<div class="space-y-6">
    <div class="flex items-center justify-between flex-wrap gap-3">
        <div class="flex gap-1 rounded-md border border-slate-300 bg-white p-1">
            @foreach ($allowedRanges as $r)
                <button type="button" wire:click="setRange('{{ $r }}')"
                    class="{{ $range === $r ? 'bg-sky-600 text-white' : 'text-slate-700 hover:bg-slate-100' }} rounded px-3 py-1 text-sm font-medium">
                    {{ $r }} дена
                </button>
            @endforeach
        </div>
        <div class="text-sm text-slate-500">
            Од вкупно <span class="font-semibold text-slate-900">{{ number_format($allTimeViews) }}</span> прегледи и
            <span class="font-semibold text-slate-900">{{ $allTimeInquiries }}</span> прашања
        </div>
    </div>

    <div class="grid gap-4 md:grid-cols-3">
        <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
            <div class="text-xs uppercase tracking-wide text-slate-500">Прегледи во период</div>
            <div class="mt-1 text-3xl font-bold text-slate-900">{{ number_format($totalViews) }}</div>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
            <div class="text-xs uppercase tracking-wide text-slate-500">Прашања во период</div>
            <div class="mt-1 text-3xl font-bold text-slate-900">{{ $inquiriesInRange }}</div>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
            <div class="text-xs uppercase tracking-wide text-slate-500">Конверзија</div>
            <div class="mt-1 text-3xl font-bold text-slate-900">{{ $conversion }}%</div>
        </div>
    </div>

    <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
        <h2 class="text-sm font-semibold text-slate-700 mb-3">Дневни прегледи</h2>

        @php
            $width = 720;
            $height = 160;
            $count = count($series);
            $barW = $count > 0 ? ($width - 20) / $count : 1;
        @endphp

        <div class="overflow-x-auto">
            <svg viewBox="0 0 {{ $width }} {{ $height + 30 }}" class="w-full max-w-3xl" preserveAspectRatio="xMidYMid meet">
                <line x1="10" y1="{{ $height }}" x2="{{ $width - 10 }}" y2="{{ $height }}"
                    stroke="#e2e8f0" stroke-width="1"/>

                @foreach ($series as $i => $point)
                    @php
                        $h = $maxDay > 0 ? ($point['count'] / $maxDay) * ($height - 10) : 0;
                        $x = 10 + $i * $barW;
                        $y = $height - $h;
                    @endphp
                    <rect x="{{ $x + 1 }}" y="{{ $y }}" width="{{ max(1, $barW - 2) }}" height="{{ $h }}"
                        fill="#0284c7" rx="1">
                        <title>{{ $point['day'] }}: {{ $point['count'] }}</title>
                    </rect>
                @endforeach

                @if ($count > 0)
                    <text x="10" y="{{ $height + 18 }}" font-size="11" fill="#64748b">
                        {{ \Carbon\Carbon::parse($series[0]['day'])->format('d.m') }}
                    </text>
                    <text x="{{ $width - 50 }}" y="{{ $height + 18 }}" font-size="11" fill="#64748b">
                        {{ \Carbon\Carbon::parse($series[$count - 1]['day'])->format('d.m') }}
                    </text>
                @endif
            </svg>
        </div>
    </div>

    <div class="rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 p-4">
            <h2 class="text-lg font-semibold text-slate-900">Прашања во период</h2>
        </div>
        @if ($inquiriesList->isEmpty())
            <div class="p-6 text-center text-slate-500">Нема прашања во избраниот период.</div>
        @else
            <ul class="divide-y divide-slate-100">
                @foreach ($inquiriesList as $inq)
                    <li class="p-4">
                        <div class="flex items-start justify-between gap-3 flex-wrap">
                            <div>
                                <div class="font-medium text-slate-900">{{ $inq->sender_name }}</div>
                                <div class="text-xs text-slate-500">
                                    <a href="mailto:{{ $inq->sender_email }}" class="hover:underline">{{ $inq->sender_email }}</a>
                                    @if ($inq->sender_phone) · {{ $inq->sender_phone }} @endif
                                </div>
                            </div>
                            <div class="text-xs text-slate-500">{{ $inq->created_at->diffForHumans() }}</div>
                        </div>
                        <p class="mt-2 text-sm text-slate-700 whitespace-pre-line">{{ \Illuminate\Support\Str::limit($inq->body, 280) }}</p>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</div>
