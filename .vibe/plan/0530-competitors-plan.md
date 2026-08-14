# Competitor Price Scraping Feature

## Context
The admin wants to track competitor prices for products. Each product can have multiple competitor URLs (e.g., from hoszigetelorendszer.com). A background job scrapes price, sale price, image URL, and description daily (2:00 AM cron) or on manual trigger. Scraped data is visible in Filament admin via a RelationManager on the Product edit page, with easy copy-paste of competitor descriptions.

---

## Decisions
- **Queue**: Database driver (requires `jobs` table migration)
- **UI**: RelationManager tab on Product edit page
- **Parser**: `symfony/dom-crawler` + `symfony/css-selector`
- **Cron**: Daily at 02:00

---

## 1. Database Migrations

### 1a. Add `manufacturer_website` to `shop_products`
```
database/migrations/YYYY_MM_DD_add_manufacturer_website_to_shop_products.php
```
- `manufacturer_website` — string, nullable

### 1b. Create `shop_competitor_links` table
```
database/migrations/YYYY_MM_DD_create_shop_competitor_links_table.php
```
Columns:
- `id`
- `product_id` — FK → shop_products, cascade delete
- `url` — string (the competitor product URL)
- `competitor_name` — string (auto-derived from domain, e.g. "hoszigetelorendszer.com")
- `scraped_price` — decimal(10,2), nullable
- `scraped_sale_price` — decimal(10,2), nullable
- `scraped_image_url` — string, nullable
- `scraped_description` — longtext, nullable
- `scraped_others` - json, nullable
- `last_scraped_at` — timestamp, nullable
- `meta` - JSON, nullable
- `scrape_status` — enum('pending','success','failed'), default 'pending'
- `scrape_error` — text, nullable
- `timestamps`

### 1c. Create `jobs` table for database queue
```
php artisan queue:table && php artisan migrate
```

---

## 2. Composer Dependency
```
composer require symfony/dom-crawler symfony/css-selector
```

---

## 3. Models

### `app/Models/Shop/CompetitorLink.php`
- Table: `shop_competitor_links`
- Fillable: `product_id`, `url`, `competitor_name`, `scraped_price`, `scraped_sale_price`, `scraped_image_url`, `scraped_description`, `last_scraped_at`, `scrape_status`, `scrape_error`
- Casts: `scraped_price` → decimal:2, `scraped_sale_price` → decimal:2, `last_scraped_at` → datetime, `scrape_status` → enum cast
- Relationship: `product()` → BelongsTo Product
- Boot: auto-derive `competitor_name` from URL domain on creating

### Update `app/Models/Shop/Product.php`
- Add `manufacturer_website` to fillable/casts
- Add `competitorLinks()` → hasMany(CompetitorLink::class)

---

## 4. Scraping Services

### `app/Services/Scraping/ScrapedData.php` (DTO)
Simple readonly class / plain object with: `price`, `salePrice`, `imageUrl`, `description`

### `app/Services/Scraping/CompetitorScraperInterface.php`
```php
interface CompetitorScraperInterface {
    public function supports(string $url): bool;
    public function scrape(string $url): ScrapedData;
}
```

### `app/Services/Scraping/Scrapers/HoszigetelorendszerScraper.php`
- `supports()`: checks if domain is `hoszigetelorendszer.com`
- `scrape()`:
  - HTTP GET with Laravel Http facade (User-Agent header, timeout 30s)
  - Parse HTML with `symfony/dom-crawler`
  - Extract price, sale price, product description, main image src
  - CSS selectors determined by inspecting the target site
  - Returns `ScrapedData`

### `app/Services/Scraping/CompetitorLinkScraperService.php`
- Accepts array of `CompetitorScraperInterface` implementations (injected via service container)
- `scrape(CompetitorLink $link): void`
  - Finds matching scraper by `supports()`
  - Calls `scrape()`, saves result to `CompetitorLink` model
  - Sets `scrape_status = 'success'` + `last_scraped_at = now()`
  - On exception: sets `scrape_status = 'failed'` + `scrape_error = $e->getMessage()`
- Register scrapers in `AppServiceProvider` or dedicated `ScrapingServiceProvider`

---

## 5. Queue Job

### `app/Jobs/ScrapeCompetitorLinkJob.php`
- Implements `ShouldQueue`
- Constructor: accepts `CompetitorLink $link` (model binding)
- `handle(CompetitorLinkScraperService $service)`: calls `$service->scrape($this->link)`
- Queue: `default`
- Tries: 3
- Retry after: 60 seconds
- Timeout: 60 seconds

---

## 6. Artisan Command

### `app/Console/Commands/ScrapeCompetitorPrices.php`
Signature: `scrape:competitor-prices {--product= : Product ID or slug} {--delay=5 : Seconds between jobs}`

Logic:
- If `--product` given: dispatch jobs for that product's competitor links only
- Otherwise: dispatch for all `CompetitorLink::all()`
- Stagger dispatch with `->delay(now()->addSeconds($i * $delay))` to avoid rate limiting
- Output progress with `$this->components->twoColumnDetail()` (follow ImportProducts pattern)

---

## 7. Cron Schedule

### `app/Console/Kernel.php` → `schedule()` method
```php
$schedule->command('scrape:competitor-prices')->dailyAt('02:00');
```

---

## 8. Queue Config

Update `.env`:
```
QUEUE_CONNECTION=database
```

Update `.env.example` the same way.

---

## 9. Filament Admin

### 9a. Add `manufacturer_website` to ProductForm
In `app/Filament/Clusters/Products/Resources/Products/Schemas/ProductForm.php`:
- Add a `TextInput::make('manufacturer_website')` field with URL validation
- Place it in the Basic Info section below `slug`, or after `description`
- Label: "Gyártó weboldala", placeholder: "https://..."

### 9b. RelationManager
File: `app/Filament/Clusters/Products/Resources/Products/RelationManagers/CompetitorLinksRelationManager.php`

**Table columns:**
- `competitor_name` — badge
- `url` — copyable, limit 60, link to open in browser tab
- `scraped_price` — money format, label "Ár"
- `scraped_sale_price` — money format, label "Akciós ár", nullable
- `scraped_image_url` — label "Kép URL", copyable, limit 40
- `last_scraped_at` — since/relative, label "Utolsó frissítés"
- `scrape_status` — badge (pending=warning, success=success, failed=danger)

**Table header actions:**
- "Összes scrape-elése" — dispatches `ScrapeCompetitorLinkJob` for all links of this product

**Row actions:**
- `EditAction` — edit URL only
- Custom `ScrapeNow` action — dispatches `ScrapeCompetitorLinkJob` for this single link, success notification
- Custom `ViewDescription` action — opens a Filament modal with:
  - `TextEntry` showing full `scraped_description` in a scrollable area
  - A "Másolás" (Copy) button using Alpine.js clipboard copy
  - This allows copy-paste into the product description

**Form (for Add/Edit):**
- `url` — TextInput, URL validation, required, label "Konkurencia URL"
- `competitor_name` is auto-derived on save (model boot)

### 9c. Register RelationManager
In `app/Filament/Clusters/Products/Resources/ProductResource.php`:
```php
public static function getRelations(): array
{
    return [
        // ... existing
        CompetitorLinksRelationManager::class,
    ];
}
```

---

## 10. Service Provider Binding
In `AppServiceProvider` (or new `ScrapingServiceProvider`):
```php
$this->app->tag([HoszigetelorendszerScraper::class], 'competitor-scrapers');
$this->app->bind(CompetitorLinkScraperService::class, function ($app) {
    return new CompetitorLinkScraperService($app->tagged('competitor-scrapers'));
});
```

---

## 11. Tests
- `tests/Feature/ScrapeCompetitorLinkJobTest.php` — mock HTTP response, assert model fields updated
- `tests/Unit/HoszigetelorendszerScraperTest.php` — fixture HTML → assert correct price/description extraction
- `tests/Feature/CompetitorLinksRelationManagerTest.php` — Filament table/form interaction

---

## Verification
1. Run `php artisan migrate` — check `shop_competitor_links` and `jobs` tables exist
2. In Filament: open a product → "Konkurencia" tab → add URL for eps-80 product
3. Run `php artisan scrape:competitor-prices --product=eps-80-homlokzati-hoszigetelo-lemez-2-cm`
4. Start queue worker: `php artisan queue:work`
5. Check `CompetitorLink` record in DB for scraped data
6. In Filament: verify price, image URL, and description modal appear
7. Verify cron fires with `php artisan schedule:run`

---

## File Summary (new files)
```
database/migrations/*_add_manufacturer_website_to_shop_products.php
database/migrations/*_create_shop_competitor_links_table.php
app/Models/Shop/CompetitorLink.php
app/Services/Scraping/ScrapedData.php
app/Services/Scraping/CompetitorScraperInterface.php
app/Services/Scraping/Scrapers/HoszigetelorendszerScraper.php
app/Services/Scraping/CompetitorLinkScraperService.php
app/Jobs/ScrapeCompetitorLinkJob.php
app/Console/Commands/ScrapeCompetitorPrices.php
app/Filament/Clusters/Products/Resources/Products/RelationManagers/CompetitorLinksRelationManager.php
```

## Modified files
```
app/Models/Shop/Product.php              — add competitorLinks(), manufacturer_website
app/Console/Kernel.php                   — add cron schedule
app/Providers/AppServiceProvider.php     — bind scraper service
app/Filament/Clusters/Products/Resources/Products/Schemas/ProductForm.php  — add manufacturer_website field
app/Filament/Clusters/Products/Resources/ProductResource.php               — register RelationManager
.env / .env.example                      — QUEUE_CONNECTION=database
```
