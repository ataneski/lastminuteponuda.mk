# lastminuteponuda.mk

Платформа за last minute туристички понуди од македонските туристички
агенции. Агенциите можат брзо и лесно да поставуваат огласи со цени,
термини и карактеристики; патниците ги разгледуваат најновите понуди на
едно место.

## Стек

- Laravel 13 (PHP 8.3+)
- Laravel Breeze за auth (register / login / password reset / profile)
- Livewire 4 (single-file components) за интерактивната агенциска форма и
  пребарувањето во индексот на огласи
- Blade за server-rendered HTML страниците
- Tailwind CSS 4 + Alpine.js (со Vite)
- Pest 4 за тестови
- SQLite за развој (`database/database.sqlite`); лесно се прешалтува на
  PostgreSQL/MySQL преку `.env` (види блок во `.env.example`)

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

| URL | Име | Auth | Опис |
| --- | --- | --- | --- |
| `/` | `home` | public | Hero + последни 6 огласи |
| `/oglasi` | `listings.index` | public | Сите огласи + Livewire пребарување/филтер |
| `/oglasi/{id}` | `listings.show` | public | Детали за оглас + контакт со агенција |
| `/login`, `/register` | Breeze | public | Auth екрани |
| `/agencija/nov-oglas` | `listings.create` | auth | Livewire форма за нов оглас |
| `/agencija/moi-oglasi` | `listings.mine` | auth | Огласи на најавената агенција |
| `/dashboard` | `dashboard` | auth | Redirect → `listings.mine` (за Breeze) |
| `/profile` | `profile.edit` | auth | Профил на корисникот |

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

## Тестови

```bash
php artisan test
```

43 теста (10 за Livewire формата, 8 за page рендерирање, 25 за Breeze
auth/profile). Покриваат:
- Validation на сите полиња (required, in:, date order, image size)
- Создавање оглас со auth user_id и features
- Image upload + storage assertion
- Auth gating на agency рутите
- Изолација: едниот корисник не ги гледа туѓите огласи во „Мои огласи"

## Безбедност (по дизајн)

- CSRF token на сите Livewire и Blade форми (built-in)
- Server-side валидација на сите полиња (`#[Validate]` атрибути)
- Eloquent + параметризирани queries — нема raw SQL
- `$fillable` ограничен на моделот (без `$guarded = []`)
- Auth gate во рутите (`middleware('auth')`), не во middleware/HTTP-слој —
  избегнат паттернот од CVE-2025-29927
- Lозинките се хеширани преку `bcrypt` (Laravel default)
- Upload-ите се валидираат како `image|max:4096` пред да стигнат до диск
- Симлинк `public/storage` → `storage/app/public` (без exposed
  системски патишта)

## Префрлање на Postgres

1. Во `.env` закоментирај `DB_CONNECTION=sqlite` и откоментирај го
   Postgres блокот
2. Постави Postgres база и корисник
3. `php artisan migrate:fresh --seed`

Без промени на код — сите queries поминуваат низ Eloquent и работат на
SQLite, MySQL и Postgres подеднакво.
