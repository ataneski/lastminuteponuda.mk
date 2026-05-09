# lastminuteponuda.mk

Платформа за last minute туристички понуди од македонските туристички
агенции. Агенциите можат брзо и лесно да поставуваат огласи со цени,
термини и карактеристики; патниците ги разгледуваат најновите понуди на
едно место.

## Стек

- Laravel 13 (PHP 8.3+)
- Livewire 4 (single-file components) за интерактивната агенциска форма и
  пребарувањето во индексот на огласи
- Blade за server-rendered HTML страниците
- Tailwind CSS 4 (со Vite)
- SQLite за развој (`database/database.sqlite`); лесно се прешалтува на
  MySQL/PostgreSQL преку `.env`

## Стартување локално

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm install && npm run build
php artisan serve
```

Отворете `http://127.0.0.1:8000`.

За development со hot reload:

```bash
composer run dev
```

## Главни рути

| URL | Име | Опис |
| --- | --- | --- |
| `/` | `home` | Hero + последни 6 огласи |
| `/oglasi` | `listings.index` | Сите огласи + Livewire пребарување/филтер |
| `/oglasi/{id}` | `listings.show` | Детали за оглас + контакт со агенција |
| `/agencija/nov-oglas` | `listings.create` | Livewire форма за нов оглас |

## Модел `Listing`

`app/Models/Listing.php` со миграција `database/migrations/*_create_listings_table.php`:

- агенција: `agency_name`, `agency_contact`
- понуда: `title`, `destination`, `country`, `hotel_name`, `hotel_stars` (1–5)
- услуги: `board_type` (без оброци / појадок / полупансион / полн / all
  inclusive / ultra all inclusive), `transport` (автобус / авион /
  сопствен / траект)
- термин и цена: `departure_date`, `return_date`, `nights`,
  `price_per_person`, `currency` (EUR/MKD/USD), `available_seats`
- содржина: `description`, `features` (JSON низа), `image_url`

Сите енумерации со македонски лабели се на самиот модел
(`Listing::BOARD_TYPES`, `Listing::TRANSPORTS`).

## Livewire компоненти

- `resources/views/components/⚡listing-form.blade.php` — агенциска форма
  со server-side валидација (`#[Validate]`) и chip-ови за карактеристики
  (preset + custom)
- `resources/views/components/⚡listings-index.blade.php` — листа со
  пагинација и live пребарување (debounced URL state)

## Безбедност (по дизајн)

- CSRF token на сите Livewire requests (built-in)
- Server-side валидација на сите полиња (`#[Validate]` на компонентата)
- Eloquent + параметризирани queries — нема raw SQL
- `$fillable` ограничен модел (без `$guarded = []`)
- Без auth логика во middleware (избегнат CVE-2025-29927 паттерн —
  важи само за Next.js, но истиот принцип го применуваме тука)
