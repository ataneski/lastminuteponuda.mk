# Стратегија за монетизација — lastminuteponuda.mk

> Жив документ. Сите бројки се проценки за македонскиот пазар во 2026 г.
> и треба да се ревалидираат секои 3 месеци со реални податоци од сајтот.

## TL;DR

Прв период (0–6 мес.) — **сè бесплатно**, цел е критична маса на огласи и
посетители. По 6 мес. со 5K+ unikатни посетители месечно се вклучуваат
**Featured boost** (per-listing) и **Pro subscription** (freemium tier).
Социјалните мрежи се користат како **multiplier**, не како одделен
канал — bundle-ирани со Featured пакетите. Plaќањата за МК се прават
преку virtual POS (CaSys/Halkbank/NLB) или manual фактурирање — Stripe
не работи. Реална таргетирана MRR во година 2: **80,000–120,000 MKD/мес.**

---

## 1. Контекст: македонски пазар

| Параметар | Проценка |
| --- | --- |
| Активни туристички агенции | 100–500 |
| Дигитално присутни агенции | ~150 |
| Готови да платат месечно >€10 | ~50–80 |
| Просечен приход по агенција (мес.) | 50,000–500,000 MKD |
| Маркетинг буџет на агенција (мес.) | ~5–10% од приходот |
| Сезонски peak | март–септември |
| Мртва сезона | ноември–јануари |

**Импликации за price design-от:**
- Месечни paywall-и под 1,000 MKD имаат шанса; над 2,500 MKD само за
  Premium/multi-user
- Per-deal плаќања (Featured) подобро се конвертираат од subscription
  за мали агенции
- Сезоналноста бара или **flexible pause** на subscription, или **annual
  prepay со попуст** (за стабилен cash flow)
- **Картичните плаќања во МК:** Stripe **не работи**. Опции:
  - **Virtual POS** преку Halkbank / NLB / Komercijalna / Stopanska —
    bесmесечен трошок 1,500–3,000 MKD + 1.5–2.5% по трансакција
  - **CaSys** (картични процесор) — слично
  - **Manual gjiro фактурирање** — без интеграција, admin рачно
    активира план; добар MVP пристап
  - **PayPal** — работи но провизија е висока за МК добивател
  - **Stripe Atlas (US LLC)** — заобиколува, но overkill за пилот

---

## 2. Модели на монетизација

### A. Freemium subscription (агенциски tier)

Месечен/годишен план за агенциите.

| Tier | Цена/мес. | Лимит на огласи | Брендирање | Featured | Аналитика | Социјал |
| --- | --- | --- | --- | --- | --- | --- |
| **Free** | 0 | 3 активни | основно (без cover, лого ✓) | ✗ | ✗ | ✗ |
| **Pro** | 990 MKD | unlimited | целосно (cover, custom slug, акцент боја) | 1 boost/мес. | ✓ | 1 IG post/мес. |
| **Premium** | 2,490 MKD | unlimited | + verified badge | 4 boost-а/мес. | + lead-tracking | 4 IG post/мес. + story |

**Plus / минус:**
- ✓ Предвидлив MRR
- ✓ Натпреварува субјекти (Pro tier signal на сериозност)
- ✗ Висока бариера за стартап агенции
- ✗ Сезоналност — јануари може да биде болен

**Реална математика:**
- 50 Pro × 990 = 49,500 MKD
- 10 Premium × 2,490 = 24,900 MKD
- = **~74,400 MKD/мес.** во оптимистичен Y1 сценарио

### B. Featured / Boost (per-listing)

Sticky top placement + визуелен badge на огласот.

| Pakeт | Цена | Времетраење | Бонус |
| --- | --- | --- | --- |
| Boost 7 | 300 MKD | 7 дена | sticky on /oglasi |
| Boost 30 | 700 MKD | 30 дена | sticky on /oglasi + home rotation |
| Boost + Social | 1,200 MKD | 30 дена | + 1 IG post + 1 story |

**Plus / минус:**
- ✓ Лесна пордажба, без commitment
- ✓ Скалирање — повеќе огласи = повеќе boost-и
- ✓ Самостојна одлука по оглас, не претплата
- ✗ Зависи од traffic; 100 visitor/ден не е доволно
- ✗ Бара функционирачки sort + visual differentiation

**Реална математика:**
- Ако 30% од објавените огласи купат boost, и месечно има 200 нови огласи:
  60 boost × 700 MKD = **42,000 MKD/мес.**
- Со social bundle (50% upsell): 30 × 500 MKD доплата = +15,000 MKD

### C. Pay-per-lead (per inquiry)

Бесплатно листирање; агенциите плаќаат за секоја пратена inquiry порака
(50–100 MKD).

**Plus / минус:**
- ✓ Чисто performance — ниска бариера
- ✓ Аdiрана со вредноста (доплата кога има интерес)
- ✗ Агенциите ги вадат своите броеви од описот да го заобиколат
  системот → треба mask + force-через платформа
- ✗ Тешко за tracking (што е „valid" lead? обвинение / спор)
- ✗ Ризично без booking flow како proof-of-conversion

**Препорака:** **НЕ ПРВО.** Вклучи откако ќе имаш цврст волумен на
inquiry и tracking infra. Може да биде рекламирано како опционално
„pay-as-you-go" во Y2.

### D. Sponsorship + display

| Производ | Цена | Времетраење |
| --- | --- | --- |
| Hero banner на homepage | 5,000 MKD | 7 дена |
| Newsletter sponsorship | 3,000 MKD | по edition |
| „Понуда на неделата" (hero + IG + newsletter) | 12,000 MKD | 7 дена |

**Plus / минус:**
- ✓ Високо visible
- ✓ Премиум цена за агенциите со buget
- ✗ Бара значаен traffic + email листа
- ✗ Може да загрози UX

---

## 3. Социјалните мрежи како multiplier (НЕ одделен модел)

Социјалните се **најсилниот upsell** за сите горни модели, не самостоен
извор. Зошто:

- Просечната МК агенција има 500–2,000 IG следачи и слаб engagement
- Платформата со 10K+ следачи има 5–10× поголем reach од нив
- Тие плаќаат за **reach којшто инаку не можат да го купат**

### Реална математика (фаза 2)

Со 20K IG следачи и 3% engagement:
- 600 reactions / impressions по post
- 4 sponsored posts / неделно × 2,000 MKD = **32,000 MKD/мес.**
- + Boost-социјал bundle (B): +500 MKD × ~30 boost-а = +15,000 MKD/мес.

**Заклучок:** социјалот сам не носи 32K, ама го прави Featured pakeт-от
далеку поатрактивен. Без социјал, Boost се продава како placement;
со социјал, се продава како **reach + placement**.

### Што се гради за да биде скалабилно

1. **Auto-generated IG card per oglas** — 1080×1080 PNG со наслов,
   цена, дестинација, лого на агенцијата (Intervention/Image package).
   Едно копче „Сподели на IG" → сликата готова за upload.
   *Без оваа автоматизација ќе тонеш во manual Canva работа за
   секој boost.*

2. **Boost + Social toggle** на формата за Featured — чек-бокс „+
   објави на нашиот Instagram" за +500 MKD. Креира внатрешен
   `social_promotion` запис во опашка за content тимот.

3. **UTM tagging автоматски** — секој линк од IG bio / story / post кон
   сајтот добива `?utm_source=instagram&utm_medium=...&utm_campaign=...`
   за reporting.

4. **Agency social handle** на User профил — ги тагираме во post-овите,
   тие репостуваат, бесплатен reach loop.

5. **„Top 10 of the week" newsletter** — еднонеделен email; еден pakeт
   со IG карусел + newsletter feature = 12,000 MKD (option D гори).

---

## 4. Препорачана фазна стратегија

### Фаза 1: Раст (0–6 мес.) — БЕЗ ПЛАЌАЊЕ

**Што:**
- Сè бесплатно за сите агенции
- Активна продажба на агенции за листирање („Бесплатно е, само ставете
  огласи")
- Активна агрегација на огласи од отворени канали (нивните FB страници)
  за да има content од ден 1
- Пушти IG, TikTok, Facebook страница; daily „best deal of the day"
- SEO агресивно: sitemap веќе е готов, JSON-LD веќе е готов

**KPI цели за крајот на Ф1:**
- 30+ активни агенции
- 200+ листирани огласи
- 5,000 unique визитори/мес.
- 3,000 IG следачи

**Зошто без paywall:** marketplace неуспех = 80% од платформите што се
наплатиле прерано. Прв треба двостраен tier (агенции + посетители),
парите доаѓаат подоцна.

### Фаза 2: Монетизација (6–12 мес.) — B + lite A

**Што да се вклучи:**
1. **Featured boost** (option B) — еден pakeт во старт: Boost 30 = 700 MKD
2. **Pro tier** (option A — само Free + Pro прв миг): Free со cap 3
   огласи, Pro 990 MKD/мес. за unlimited + analytics + social post
3. **„Boost + Social" bundle** (1,200 MKD) штом IG достигне 5K следачи
4. **Manual фактурирање** — без virtual POS уште; admin активира
   платените функции рачно по уплата на жиро

**Не вклучувај:**
- Pay-per-lead (C) — рано
- Premium tier (целосен А) — рано, прво валидирај Pro
- Display sponsorship (D над hero banner) — нема доволно traffic

**KPI цели за крајот на Ф2:**
- 50–80 платежни агенции (Pro + Boost комбинирано)
- MRR ~50,000–80,000 MKD
- 15,000 unique визитори/мес.
- 10,000 IG следачи

### Фаза 3: Скалирање (12+ мес.) — додаваме D, можеби C

**Што:**
- Newsletter sponsorship + hero banner (D) — кога traffic е таму
- Premium tier (option A полно)
- Lead tracking + опционален Pay-per-lead (C) — кога имаме доказ
  за конверзија преку платформата (не bypass)
- **Booking flow** (BIG) — кога имаме barganing power: take 5–10% од
  bookинг ако правиме end-to-end checkout
- **Virtual POS интеграција** (CaSys/Halkbank) — за self-serve
  subscription плаќања

**KPI цели:**
- MRR 100,000–150,000 MKD
- 30,000 unique визитори/мес.
- Прв booking flow MVP активиран

---

## 5. Anti-patterns (што да се ИЗБЕГНЕ)

1. **Paywall во прв ден** — мртви marketplace-и се правени така
2. **Stripe-only checkout** — не работи во МК; ги исклучуваш агенциите
3. **Subscription над 2,500 MKD без enterprise sales** — не се продава
   self-serve во овој пазар
4. **Pay-per-lead без bypass protection** — телефонските броеви во
   описот ќе го убијат бизнисот; mask или force-през платформа
5. **Reklamiranje od третi брендови (Booking.com банери, Google
   AdSense)** — го губи UX-от и сигналира non-credibility пред
   агенциите што таргетираат
6. **„Бесплатно засекогаш" gимик** — обично резултира со ниска квалитета
   на огласите; cap-от од 3 огласи е fer и охрабрува upgrade
7. **Annual-only plans** — премногу commitment за тестен период;
   monthly first, annual со 15% попуст подоцна

---

## 6. Implementation roadmap (за платформата)

Овие се **технички градби** што ги овозможуваат горните модели. Не се
сите за веднаш; постави ги по фазен план.

### За Фаза 2 (минимум за монетизација)

| Feature | Естимат | За кој модел | Приоритет |
| --- | --- | --- | --- |
| `featured_until` колона + sticky sort + badge | 1.5 ч | B | P0 |
| Admin tool за manual активирање boost/Pro | 2 ч | A, B (manual билинг) | P0 |
| `subscription_tier` enum + cap на free огласи | 1.5 ч | A | P0 |
| Auto-generated IG card per oglas | 2.5 ч | Social bundle | P1 |
| UTM tracking helper | 0.5 ч | сите | P1 |
| Inquiry counter / lead tracking schema | 1.5 ч | C (rana data) + analytics | P1 |
| Analytics dashboard за агенциите (views, inquiries) | 3 ч | Pro tier value prop | P1 |
| Agency social_handle поле | 0.3 ч | Social loop | P2 |

**Вкупно P0:** ~5 часа. Со тоа можеш да фактурираш и да активираш
функции — без integratiран checkout.

### За Фаза 3 (self-serve платежи)

| Feature | Естимат | За кој модел |
| --- | --- | --- |
| Halkbank / CaSys virtual POS интеграција | 8–16 ч | A self-serve |
| Stripe-style subscription state machine | 6 ч | A |
| Booking flow со escrow / split payments | 40+ ч | Commission (нов) |
| Pay-per-lead билинг + bypass detection | 12 ч | C |
| Lead masking (proxy phone/email) | 8 ч | C anti-bypass |

---

## 7. Револуенција и cost структура

### Y1 (oптимистички, ама реално сценарио)

| Извор | Месечно (просек Y1) |
| --- | --- |
| Pro subscriptions (50 × 990) | 49,500 MKD |
| Boost (60/мес. × 700) | 42,000 MKD |
| Boost-Social bundle (30 × 500 upsell) | 15,000 MKD |
| Sponsored IG posts (3 × 2,000) | 6,000 MKD |
| **Total** | **~112,500 MKD** |

### Operating costs (приказ)

| Ставка | Цена/мес. (MKD) |
| --- | --- |
| Hosting (Vapor / Forge / VPS) | 1,000–3,000 |
| Email (Postmark / Mailgun) | 500–1,500 |
| Storage (S3 / Spaces) | 500–1,000 |
| Domain + SSL | ~100 |
| Virtual POS месечен (Y2) | 2,000–3,000 |
| Content тим (1 person freelance, IG) | 15,000–25,000 |
| **Total** | **~20,000–35,000** |

**Net Y1:** ~80,000 MKD/мес. при оптимистички сценарио. Реално Y1 е
0–30,000 MKD/мес. поради ramp-up на supply и demand. Y2 е каде моделот
почнува да noси.

---

## 8. Метрики за следење (по фаза)

### Фаза 1
- DAU / WAU на сајтот
- Број на активни агенции (постирале во последните 30 дена)
- Бројот на нови огласи / неделно
- IG следачи + engagement rate
- Bounce rate на /oglasi/{id}

### Фаза 2 (додадени)
- MRR + churn rate на Pro
- Boost conversion rate (% од нови огласи што купуваат boost)
- ARPA (Average Revenue Per Agency)
- Inquiry rate (просек по оглас) — раниот сигнал за option C
- LTV / CAC

### Фаза 3 (додадени)
- Booking conversion rate (ако има checkout)
- Net Revenue Retention
- Cost per lead (за option C билинг design)

---

## 9. Прашања за валидирање со 5–10 агенции пред Фаза 2

Пред да буде вчитан paywall-от, разговор со sample од агенции:

1. „Колку огласи постирате месечно во peak/off-season?" → cap на Free
2. „Колку платите сега за реклама на FB/IG?" → benchmark за Pro tier
3. „Колкав % од запросите ги добивате од вашата страница vs. директно?"
   → подлога за option C
4. „Кои функции (за вас агенции) од platформата би биле „must-have"?"
   → што да се ставка во Pro vs Free
5. „Дали би купиле boost за 700 MKD ако сте сигурни во +50 visits?" →
   willingness-to-pay test
6. „Дали би прифатиле split на bookинг (5–10%) ако ви носиме
   потврдени резервации?" → option commission viability

---

## 10. Промени во овој документ

| Датум | Промена | Автор |
| --- | --- | --- |
| 2026-05-09 | Прва верзија — пост-Phase 0 (платформата веќе функционална) | Claude |
