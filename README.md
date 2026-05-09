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
| `/` | `home` | public | Hero + последни 6 активни огласи |
| `/oglasi` | `listings.index` | public | Активни огласи + Livewire филтри; featured огласи прво |
| `/oglasi/{id}` | `listings.show` | public | Детали + JSON-LD + inquiry форма; +1 view counter |
| `/oglasi/{id}/social-card.png` | `listings.social-card` | public | Auto-generated 1080×1080 IG card |
| `/agencija/{slug}` | `agency.show` | public | Јавна страница на агенција со лого, бои, активни огласи |
| `/planovi` | `upgrade` | public | Free / Pro / Premium pricing страница |
| `/sitemap.xml` | `sitemap` | public | Динамичен sitemap |
| `/login`, `/register` | Breeze | public | Auth екрани |
| `/agencija/nov-oglas` | `listings.create` | auth | Нов оглас (Free cap = 3 активни) |
| `/agencija/profil` | `agency.profile.edit` | auth | Бренд (лого, cover, бои, slug, контакт, IG/FB handles) |
| `/agencija/analitika` | `agency.analytics` | auth+pro | Прегледи, прашања, конверзија, топ огласи |
| `/agencija/oglas/{id}/uredi` | `listings.edit` | auth+owner | Уредување — само сопственикот |
| `/agencija/oglas/{id}` (DELETE) | `listings.destroy` | auth+owner | Бришење — само сопственикот |
| `/agencija/moi-oglasi` | `listings.mine` | auth | Сите огласи на најавената агенција |
| `/admin` | `admin.index` | admin | Сите агенции, tier ажурирање |
| `/admin/listings` | `admin.listings` | admin | Сите огласи, featured boost |
| `/dashboard` | `dashboard` | auth | Redirect → `listings.mine` |
| `/profile` | `profile.edit` | auth | Профил на корисникот (Breeze) |

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

## Монетизација

Платформата има изграден основен monetization slojer (Phase 2 од
[docs/monetizacija-lastminute.md](docs/monetizacija-lastminute.md)):

- **Subscription tiers** на User: `free` (cap 3 активни огласи),
  `pro`, `premium`. `effectiveTier()` се враќа на `free` ако
  `subscription_until` е во минатото
- **Featured boost** — `featured_until` колона; огласите се прво
  во индексот + amber badge
- **Lead tracking** — секое прашање се чува во `inquiries` табела
  (sender, body, IP hash); `views_count` се инкрементира на детали
- **Analytics dashboard** за Pro: прегледи, прашања, конверзија, топ
  огласи (gated со `isPro()`)
- **Auto-generated IG card** (1080×1080 PNG) — `SocialCardGenerator`
  со DejaVu Sans, accent color од агенцискиот профил
- **Admin tool** за manual активирање tier + featured boost (gated
  со `is_admin` middleware)
- **UTM helper** (`App\Support\Utm::tag()`) за tracking на social
  campaign-ите

Self-serve платежи нема (по дизајн, Phase 2). Активирањето на Pro
и featured boost е manual преку admin панелот, по уплата на gjiro
или контакт со клиентот.

## Тестови

```bash
php artisan test
```

124 теста (330 assertions) во `tests/Feature/`:

- `ListingPagesTest` — рендерирање, 404, auth gating, isolation
- `ListingFormTest` — валидација, создавање, features, image upload
- `ListingCrudTest` — edit/delete + Policy enforcement (owner-only)
- `ListingExpiryTest` — `expires_at`, скривање на истечени, 404
- `ListingFiltersTest` — сите 8 филтри + комбинации + clear
- `ListingImagesTest` — multi-image upload, max 10, cascade delete, append
- `ListingNotificationsTest` — rate limit (5/час), email на creation
- `SeoTest` — sitemap, meta tags, JSON-LD, robots.txt
- `AgencyProfileTest` — slug auto-gen, public страница, accent color,
  логo upload, валидација на slug + accent
- `ListingInquiryTest` — рендерирање, mail queueing, validation,
  honeypot, rate limit (3/час по IP), fallback на `agency_contact`
- `MonetizationTest` — tier resolution, free cap (3 активни), Pro
  unlimited, expired subscription falls back to free, featured
  badge + sort, view counter (без owner views), inquiry logging,
  analytics gating, admin tier update + feature/unfeature, social
  card 1080×1080 PNG, UTM helper
- Breeze auth тестови (registration, login, password reset, profile)

## Безбедност (по дизајн)

- CSRF token на сите Livewire и Blade форми (built-in)
- Server-side валидација на сите полиња (`#[Validate]` атрибути и `rules()`)
- Eloquent + параметризирани queries — нема raw SQL
- `$fillable` ограничен на моделот (без `$guarded = []`)
- Auth gate во рутите (`middleware('auth')`), не во middleware/HTTP-слој —
  избегнат паттернот од CVE-2025-29927
- `ListingPolicy` за edit/delete (только сопственикот) преку
  `$this->authorize('update'|'delete', $listing)` во Controller, Livewire
  компонента, и Blade `@can` директива
- Лозинките се хеширани преку `bcrypt` (Laravel default)
- Upload-ите се валидираат како `image|max:4096` × макс. 10 пред диск
- Rate limit: 5 нови огласи на час по корисник (RateLimiter)
- Симлинк `public/storage` → `storage/app/public` (без exposed
  системски патишта)
- Истечените огласи се сокриваат од јавноста и враќаат 404 (само
  сопственикот ги гледа)

## SEO

- Динамичен `/sitemap.xml` со сите активни огласи + `lastmod`
- `robots.txt` со `Sitemap:` директива и `Disallow: /agencija/`
- Per-page meta: `<title>`, `<meta description>`, `<link rel="canonical">`
- Open Graph + Twitter Card мета на сите страници
- JSON-LD `TouristTrip` schema на детали страницата (Google Rich Results
  ready) — со `Place`, `Offer`, `TravelAgency` сегменти

## Префрлање на Postgres

1. Во `.env` закоментирај `DB_CONNECTION=sqlite` и откоментирај го
   Postgres блокот
2. Постави Postgres база и корисник
3. `php artisan migrate:fresh --seed`

Без промени на код — сите queries поминуваат низ Eloquent и работат на
SQLite, MySQL и Postgres подеднакво.
