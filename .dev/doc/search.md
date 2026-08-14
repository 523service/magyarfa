# Search System Documentation

## Overview

The search system has two parts:

1. **Header autocomplete overlay** — Alpine.js component, backend-proxied via Laravel Scout/Algolia
2. **Full search results page** (`/kereses`) — client-side InstantSearch.js powered by Algolia

No Algolia JS client runs in the browser for the autocomplete; all Scout queries happen server-side.

---

## Architecture

```
Browser
  │
  ├── Header autocomplete (Alpine.js)
  │     ├── GET /kereses/defaults      → SearchController::defaults()   (DB query)
  │     └── GET /kereses/autocomplete  → SearchController::autocomplete() (Scout/Algolia)
  │
  └── /kereses page (InstantSearch.js)
        └── Algolia JS client → shop_products index (direct, browser→Algolia)
```

---

## Environment Variables

```dotenv
SCOUT_DRIVER=algolia
ALGOLIA_APP_ID=your_app_id
ALGOLIA_SECRET=your_admin_key          # server-side only, never VITE_-prefixed
ALGOLIA_SEARCH_KEY=your_search_key     # read-only

VITE_ALGOLIA_APP_ID="${ALGOLIA_APP_ID}"
VITE_ALGOLIA_SEARCH_KEY="${ALGOLIA_SEARCH_KEY}"
```

---

## Algolia Indices

| Index | Model | Configured in |
|---|---|---|
| `shop_products` | `App\Models\Shop\Product` | `config/scout.php` |
| `shop_categories` | `App\Models\Shop\Category` | `config/scout.php` |

### Index Settings (`config/scout.php`)

**shop_products**
- `searchableAttributes`: name, sku, brand_name, category_names, description
- `customRanking`: desc(featured), desc(price)
- `attributesForFaceting`: brand_name (searchable), category_names (searchable), brand_id (filterOnly), category_ids (filterOnly)

**shop_categories**
- `searchableAttributes`: name, description

### Artisan Commands

```bash
# Push index settings to Algolia
php artisan scout:sync-index-settings

# Index all records
php artisan scout:import "App\Models\Shop\Product"
php artisan scout:import "App\Models\Shop\Category"
```

---

## Model Integration

### Product (`app/Models/Shop/Product.php`)

```php
use Laravel\Scout\Searchable;

// Only visible products are indexed
public function shouldBeSearchable(): bool
{
    return $this->is_visible === true;
}

// Prevents N+1 during scout:import
public function makeAllSearchableUsing(Builder $query): Builder
{
    return $query->with(['brand', 'categories', 'media', 'sharedMedia']);
}

public function toSearchableArray(): array
{
    return [
        'objectID'        => $this->id,
        'name'            => $this->name,
        'slug'            => $this->slug,
        'sku'             => $this->sku,
        'description'     => strip_tags((string) $this->description),
        'price'           => (float) $this->price,
        'price_formatted' => number_format((float) $this->price, 0, ',', ' ') . ' Ft',
        'featured'        => $this->featured,
        'brand_id'        => $this->shop_brand_id,
        'brand_name'      => $this->brand?->name,
        'category_ids'    => $this->categories->pluck('id')->toArray(),
        'category_names'  => $this->categories->pluck('name')->toArray(),
        'image_url'       => $this->getMainImageUrl('thumb'),
        'url'             => route('product.show', $this->slug),
    ];
}
```

### Category (`app/Models/Shop/Category.php`)

```php
use Laravel\Scout\Searchable;

public function shouldBeSearchable(): bool
{
    return $this->is_visible === true;
}

public function toSearchableArray(): array
{
    return [
        'objectID'    => $this->id,
        'name'        => $this->name,
        'slug'        => $this->slug,
        'description' => strip_tags((string) $this->description),
        'image_url'   => $this->getFirstMediaUrl() ?: $this->products->first()?->getMainImageUrl('thumb'),
        'url'         => route('category.show', $this->slug),
    ];
}
```

---

## Routes

```php
// routes/web.php
Route::get('/kereses/defaults',     [SearchController::class, 'defaults'])->name('search.defaults');
Route::get('/kereses/autocomplete', [SearchController::class, 'autocomplete'])->name('search.autocomplete');
Route::get('/kereses',              [SearchController::class, 'index'])->name('search.index');
```

---

## Backend: SearchController

**`app/Http/Controllers/SearchController.php`**

| Method | Route | Purpose |
|---|---|---|
| `index()` | GET /kereses | Renders the search results view, passes `$query` |
| `defaults()` | GET /kereses/defaults | Returns JSON `{ latest: [...], popular: [...] }` — 3 newest + 3 featured visible products |
| `autocomplete()` | GET /kereses/autocomplete?q= | Returns JSON `{ products: [...], categories: [...] }` via Scout; returns empty arrays if `strlen(q) < 2` or on exception |

### Response shape — defaults

```json
{
  "latest":  [{ "title", "url", "thumb", "meta" }],
  "popular": [{ "title", "url", "thumb", "meta" }]
}
```

### Response shape — autocomplete

```json
{
  "products":   [{ "title", "url", "thumb", "meta", "price" }],
  "categories": [{ "title", "url", "thumb", "meta" }]
}
```

---

## Header Autocomplete Overlay

**Files:**
- `resources/views/partials/shop/search-overlay.blade.php` — Alpine component
- Included from `resources/views/partials/shop/header.blade.php`
- No separate JS file; script is inlined via `@once` to survive Livewire SPA navigation

### Alpine Component (`shopSearch`)

Registered via `Alpine.data('shopSearch', (defaultsUrl, autocompleteUrl, resultsUrl) => ({...}))` inside an `alpine:init` listener.

URLs are passed as arguments from Blade:
```blade
x-data="shopSearch('{{ route('search.defaults') }}', '{{ route('search.autocomplete') }}', '{{ route('search.index') }}')"
```

**States (`panelState`):**

| State | Shown when |
|---|---|
| `default` | Panel opened, no query typed — shows latest + popular from `/defaults` |
| `loading` | Debounce timer running (300 ms after keystroke) |
| `results` | Search returned ≥ 1 result |
| `empty` | Search returned 0 results |

**Key behaviours:**
- Defaults are pre-fetched in `init()` so the panel opens instantly
- 300 ms debounce on input before firing `/autocomplete`
- `Enter` key navigates to `/kereses?q=...`
- Escape key closes the panel
- Body scroll locked while panel is open; lock cleaned up in `init()` to handle Livewire SPA edge cases
- `@once` prevents duplicate `Alpine.data` registrations across navigations

---

## Search Results Page (`/kereses`)

**Files:**
- `resources/views/shop/search.blade.php` — Blade shell
- `resources/js/search-results.js` — InstantSearch.js widgets
- Loaded via `@push('scripts') @vite('resources/js/search-results.js') @endpush`

### Layout

Mirrors the category page:
- Left sidebar (260 px desktop): `#refinement-brand` + `#refinement-category` inside `.sidebar-card` wrappers
- Right main: `.category-hero` header → `#search-box` → `#hits` grid → `#pagination`

### InstantSearch Widgets

| Widget | Container | Notes |
|---|---|---|
| `configure` | — | `hitsPerPage: 12` |
| `searchBox` | `#search-box` | `autofocus: true` |
| `stats` | `#search-stats` | Live hit count, styled as `.category-badge` pill |
| `hits` | `#hits` | Renders `.product-card` articles in a 3-col grid |
| `refinementList` | `#refinement-brand` | `attribute: 'brand_name'` |
| `refinementList` | `#refinement-category` | `attribute: 'category_names'` |
| `pagination` | `#pagination` | Hungarian prev/next labels |

### URL Routing

Custom `stateMapping` maps InstantSearch state to human-readable URL params:

| URL param | InstantSearch state |
|---|---|
| `?q=` | `shop_products.query` |
| `?brand[]` | `shop_products.refinementList.brand_name` |
| `?category[]` | `shop_products.refinementList.category_names` |
| `?page=` | `shop_products.page` |

This ensures the autocomplete's `?q=` param is picked up correctly on the results page.

---

## CSS

**Section 17** (`shop.css`) — Search Overlay:
- `.header-search`, `.search-trigger`, `.search-overlay`, `.search-panel`
- `.search-item`, `.search-skeleton-item`, `.search-empty`
- Alpine transition helpers: `.so-enter`, `.so-hidden`, `.so-visible`
- `[x-cloak]` rule to prevent Alpine flash

**Section 18** (`shop.css`) — Search Results Page (InstantSearch.js):
- `.ais-SearchBox-*` — styled to match shop input fields
- `.ais-Hits-list` — 3-col grid (mirrors `.product-grid`); 2-col on mobile
- `.ais-RefinementList-*` — checkbox list styled like sidebar category links
- `.ais-Pagination-*` — pill buttons matching existing `.pagination-wrapper`
- `.category-badge .ais-Stats-text` — live hit count as green pill badge

---

## Tests

| File | Tests |
|---|---|
| `tests/Feature/SearchControllerTest.php` | 8 tests: `/kereses` view, `defaults` JSON structure + visibility, `autocomplete` short/empty/valid query |
| `tests/Feature/ProductSearchableTest.php` | 5 tests: `toSearchableArray` keys & price format, `shouldBeSearchable`, N+1 eager loading |
| `tests/Feature/CategorySearchableTest.php` | 3 tests: `toSearchableArray` keys, `shouldBeSearchable` |

```bash
php artisan test tests/Feature/SearchControllerTest.php tests/Feature/ProductSearchableTest.php tests/Feature/CategorySearchableTest.php
```
