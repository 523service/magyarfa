# Konkurencia ár scraping

## Összefoglaló

Minden termékhez felvehető egy vagy több konkurencia link. A rendszer naponta egyszer (hajnali 2:00) automatikusan, vagy manuálisan bármikor lescrapeli az árakat, az akciós árat, a kép URL-jét és a termékleírást. Az adatok a Filament adminban termékenkénti "Konkurencia linkek" tabban jelennek meg, ahonnan a leírás könnyen copy-paste-elhető.

---

## Adatbázis

| Tábla | Mező | Leírás |
|---|---|---|
| `shop_products` | `manufacturer_website` | Gyártó weboldala (opcionális) |
| `shop_competitor_links` | `url` | Konkurencia termékoldal URL-je |
| | `competitor_name` | Domain (auto-derive), pl. `hoszigetelorendszer.com` |
| | `scraped_price` | Ár (Ft) |
| | `scraped_sale_price` | Akciós ár (Ft), ha van |
| | `scraped_image_url` | Kép URL |
| | `scraped_description` | Teljes szöveges leírás |
| | `scraped_others` | JSON — extra adatok (jövőbeli bővítéshez) |
| | `meta` | JSON — extra metaadatok |
| | `scrape_status` | `pending` / `success` / `failed` |
| | `scrape_error` | Hibaüzenet, ha a scraping sikertelen |
| | `last_scraped_at` | Utolsó sikeres scraping időpontja |

---

## Filament admin — használat

### Konkurencia link hozzáadása termékhez

1. Nyisd meg a termékszerkesztőt (Admin → Products → szerkesztés)
2. Kattints a **"Konkurencia linkek"** tabra
3. **"Link hozzáadása"** → írd be a teljes URL-t → Mentés
4. A `competitor_name` mező automatikusan kitöltődik a domain alapján

### Scraping indítása

**Egyetlen link frissítése:**  
A sor végén → **"Frissítés"** gomb → a job azonnal sorba kerül

**Összes link frissítése (termékszinten):**  
Tab fejlécében → **"Összes frissítése"** → megerősítés után az összes link sorba kerül (5 másodperces késéssel egymás között, rate limiting miatt)

### Leírás megtekintése és másolása

A sor végén → **"Leírás"** gomb → modal ablak nyílik, ahol:
- Látható az ár, akciós ár
- A kép URL melletti **"Másolás"** gombbal vágólapra kerül
- A leírás szövege **"Leírás másolása"** gombbal vágólapra kerül
- A szöveg `select-all` stílusú — rákattintva egyszerre kijelölhető

Innen copy-paste-eld a leírást a termék saját leírás mezőjébe, majd írd át.

---

## Manuális scraping — parancssor

```bash
# Összes termék összes linkjének scrape-elése (5 mp delay között)
php artisan scrape:competitor-prices

# Egy konkrét termék linkjeinek scrape-elése (slug vagy ID)
php artisan scrape:competitor-prices --product=eps-80-homlokzati-hoszigetelo-lemez-2-cm
php artisan scrape:competitor-prices --product=42

# Egyedi késleltetés (másodpercben, rate limiting finomhangolás)
php artisan scrape:competitor-prices --delay=10
```

> **Fontos:** A queue drivernek `database`-re kell állnia (`.env`: `QUEUE_CONNECTION=database`).  
> A queue workert a háttérben kell futtatni: `php artisan queue:work`

---

## Automatikus cron

A `scrape:competitor-prices` command naponta **hajnali 2:00**-kor fut automatikusan.  
Konfiguráció: `app/Console/Kernel.php` → `schedule()` metódus.

Localon tesztelni: `php artisan schedule:run`

---

## Új konkurencia oldal hozzáadása (fejlesztői feladat)

Ha egy új webshopot szeretnél hozzáadni (pl. `masikoldal.hu`):

1. **Hozz létre egy scraper osztályt:**
   ```
   app/Services/Scraping/Scrapers/MasikOldalScraper.php
   ```
   Implementáld a `CompetitorScraperInterface`-t:
   ```php
   public function supports(string $url): bool { ... }
   public function scrape(string $url): ScrapedData { ... }
   ```

2. **Regisztráld az `AppServiceProvider`-ben** (`app/Providers/AppServiceProvider.php`):
   ```php
   $this->app->bind(CompetitorLinkScraperService::class, function (): CompetitorLinkScraperService {
       return new CompetitorLinkScraperService([
           new HoszigetelorendszerScraper(),
           new MasikOldalScraper(),   // ← add hozzá
       ]);
   });
   ```

3. Ha ismeretlen domainre mutató linket adnak hozzá, a scraping `failed` státuszba kerül  
   a következő hibaüzenettel: `"Nincs scraper ehhez a domainhez: masikoldal.hu"`

---

## hoszigetelorendszer.com scraper — technikai részletek

Az oldal saját webshop rendszert használ (nem WooCommerce).

| Adat | Forrás | CSS selector / módszer |
|---|---|---|
| Ár | Inline JS (elsődleges) | `var price = 606;` regex |
| Ár | CSS selector (fallback) | `.product-prices .regular` |
| Ár | JSON-LD (utolsó fallback) | `<script type="application/ld+json">` → `offers.price` |
| Akciós ár | CSS selector | `.product-prices .sale` |
| Kép | CSS selector | `.product-image img` → relatív URL → abszolúttá alakítva |
| Kép | og:image (fallback) | `<meta property="og:image">` |
| Leírás | CSS selector | `#product-body .content-text` |
| Leírás | CSS selector (fallback) | `.content-text.block-text` |

> A JS változó (`var price`) azért elsődleges, mert egységfüggetlen — ha az oldalon "Ft / m²" helyett "Ft / csomag" szerepel, az nem töri el a parsert.

---

## Fájlok

```
app/
├── Console/Commands/ScrapeCompetitorPrices.php     — artisan command
├── Console/Kernel.php                               — cron schedule
├── Filament/Clusters/Products/Resources/Products/
│   ├── RelationManagers/CompetitorLinksRelationManager.php
│   └── Schemas/ProductForm.php                     — manufacturer_website mező
├── Jobs/ScrapeCompetitorLinkJob.php                — queue job
├── Models/Shop/CompetitorLink.php                  — model
├── Models/Shop/Product.php                         — competitorLinks() reláció
├── Providers/AppServiceProvider.php                — scraper binding
└── Services/Scraping/
    ├── CompetitorScraperInterface.php
    ├── CompetitorLinkScraperService.php
    ├── ScrapedData.php                             — DTO
    └── Scrapers/HoszigetelorendszerScraper.php

resources/views/filament/
└── competitor-description-modal.blade.php          — leírás modal

database/migrations/
├── 2026_05_31_000001_add_manufacturer_website_to_shop_products.php
├── 2026_05_31_000002_create_shop_competitor_links_table.php
└── 2026_05_31_000003_create_jobs_table.php

tests/
├── Feature/ScrapeCompetitorLinkJobTest.php
└── Unit/HoszigetelorendszerScraperTest.php
```
