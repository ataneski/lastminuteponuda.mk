<x-mail::message>
# Ново прашање за вашиот оглас

**Оглас:** {{ $listing->title }}
**Дестинација:** {{ $listing->destination }}, {{ $listing->country }}

---

**Од:** {{ $senderName }}
**Email:** [{{ $senderEmail }}](mailto:{{ $senderEmail }})
@if ($senderPhone)
**Телефон:** {{ $senderPhone }}
@endif

**Порака:**

{{ $bodyMessage }}

---

<x-mail::button :url="$url">
Прегледај оглас
</x-mail::button>

Можете да одговорите директно на оваа email адреса — Reply-To е поставено
на испраќачот.

Поздрав,<br>
{{ config('app.name', 'lastminuteponuda.mk') }}
</x-mail::message>
