<?php

use App\Models\Inquiry;
use App\Models\Listing;
use Livewire\Component;

new class extends Component
{
    public function with(): array
    {
        $user = auth()->user();

        $totalListings = $user->listings()->count();
        $activeListings = $user->activeListingCount();
        $totalViews = $user->listings()->sum('views_count');
        $totalInquiries = Inquiry::where('agency_id', $user->id)->count();
        $inquiries30d = Inquiry::where('agency_id', $user->id)
            ->where('created_at', '>=', now()->subDays(30))
            ->count();

        $topListings = $user->listings()
            ->withCount('inquiries')
            ->orderByDesc('views_count')
            ->limit(10)
            ->get();

        $recentInquiries = Inquiry::where('agency_id', $user->id)
            ->with('listing')
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        return [
            'totalListings' => $totalListings,
            'activeListings' => $activeListings,
            'totalViews' => $totalViews,
            'totalInquiries' => $totalInquiries,
            'inquiries30d' => $inquiries30d,
            'conversionRate' => $totalViews > 0
                ? round(($totalInquiries / $totalViews) * 100, 2)
                : 0,
            'topListings' => $topListings,
            'recentInquiries' => $recentInquiries,
        ];
    }
};
?>

<div class="space-y-6">
    <div class="grid gap-4 md:grid-cols-4">
        <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
            <div class="text-xs uppercase tracking-wide text-slate-500">Активни огласи</div>
            <div class="mt-1 text-2xl font-bold text-slate-900">{{ $activeListings }}</div>
            <div class="mt-1 text-xs text-slate-500">од {{ $totalListings }} вкупно</div>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
            <div class="text-xs uppercase tracking-wide text-slate-500">Прегледи</div>
            <div class="mt-1 text-2xl font-bold text-slate-900">{{ number_format($totalViews) }}</div>
            <div class="mt-1 text-xs text-slate-500">сумарно</div>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
            <div class="text-xs uppercase tracking-wide text-slate-500">Прашања</div>
            <div class="mt-1 text-2xl font-bold text-slate-900">{{ $totalInquiries }}</div>
            <div class="mt-1 text-xs text-slate-500">{{ $inquiries30d }} последни 30 дена</div>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
            <div class="text-xs uppercase tracking-wide text-slate-500">Конверзија</div>
            <div class="mt-1 text-2xl font-bold text-slate-900">{{ $conversionRate }}%</div>
            <div class="mt-1 text-xs text-slate-500">прашања/прегледи</div>
        </div>
    </div>

    <div class="rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 p-4">
            <h2 class="text-lg font-semibold text-slate-900">Топ огласи (по прегледи)</h2>
        </div>
        @if ($topListings->isEmpty())
            <div class="p-6 text-center text-slate-500">Сè уште нема податоци.</div>
        @else
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-4 py-2">Оглас</th>
                        <th class="px-4 py-2 text-right">Прегледи</th>
                        <th class="px-4 py-2 text-right">Прашања</th>
                        <th class="px-4 py-2 text-right">CR</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($topListings as $l)
                        <tr class="border-t border-slate-100">
                            <td class="px-4 py-2">
                                <a href="{{ route('listings.show', $l) }}" class="font-medium text-sky-700 hover:underline">
                                    {{ \Illuminate\Support\Str::limit($l->title, 60) }}
                                </a>
                            </td>
                            <td class="px-4 py-2 text-right">{{ number_format($l->views_count) }}</td>
                            <td class="px-4 py-2 text-right">{{ $l->inquiries_count }}</td>
                            <td class="px-4 py-2 text-right text-slate-600">
                                {{ $l->views_count > 0 ? round(($l->inquiries_count / $l->views_count) * 100, 1).'%' : '—' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <div class="rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 p-4">
            <h2 class="text-lg font-semibold text-slate-900">Последни прашања</h2>
        </div>
        @if ($recentInquiries->isEmpty())
            <div class="p-6 text-center text-slate-500">Сè уште нема прашања.</div>
        @else
            <ul class="divide-y divide-slate-100">
                @foreach ($recentInquiries as $inq)
                    <li class="p-4">
                        <div class="flex items-start justify-between gap-3 flex-wrap">
                            <div>
                                <div class="font-medium text-slate-900">{{ $inq->sender_name }}</div>
                                <div class="text-xs text-slate-500">
                                    <a href="mailto:{{ $inq->sender_email }}" class="hover:underline">{{ $inq->sender_email }}</a>
                                    @if ($inq->sender_phone) · {{ $inq->sender_phone }} @endif
                                </div>
                                @if ($inq->listing)
                                    <a href="{{ route('listings.show', $inq->listing) }}"
                                        class="text-xs text-sky-700 hover:underline">за: {{ \Illuminate\Support\Str::limit($inq->listing->title, 50) }}</a>
                                @endif
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
