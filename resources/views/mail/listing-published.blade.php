<x-mail::message>
# Огласот е објавен

Здраво {{ $listing->agency_name }},

Вашиот last minute оглас **{{ $listing->title }}** е успешно објавен на lastminuteponuda.mk.

**Дестинација:** {{ $listing->destination }}, {{ $listing->country }}
**Хотел:** {{ $listing->hotel_name }} ({{ str_repeat('★', $listing->hotel_stars) }})
**Термин:** {{ $listing->departure_date->format('d.m.Y') }} — {{ $listing->return_date->format('d.m.Y') }}
**Цена:** {{ $listing->formatted_price }} по лице

<x-mail::button :url="$url">
Прегледај оглас
</x-mail::button>

Можете да го уредите или избришете огласот од вашата страница „Мои огласи".

Поздрав,<br>
{{ config('app.name', 'lastminuteponuda.mk') }}
</x-mail::message>
