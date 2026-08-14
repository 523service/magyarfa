# Algolia Search — Products & Categories

## Context
The shop already has `laravel/scout` + `algolia/algoliasearch-client-php` installed, `SCOUT_DRIVER=algolia` set, and the `Product` model has the `Searchable` trait but no `toSearchableArray()`. The `Category` model needs the trait added. There is no search UI at all. The goal is a modern, ultra-fast search experience: instant autocomplete dropdown in the header and a full `/kereses` results page, both powered by Algolia JS (zero server round-trips after page load).

---

## Prerequisites (manual step — user must do first)
1. Go to Algolia Dashboard → **API Keys**
2. Copy the **Search-Only API Key** (read-only, NOT the Admin key)
3. Add to `.env`:
```
ALGOLIA_SEARCH_KEY=your_search_only_key
VITE_ALGOLIA_APP_ID="${ALGOLIA_APP_ID}"
VITE_ALGOLIA_SEARCH_KEY="${ALGOLIA_SEARCH_KEY}"
```
Also add the same (with empty values) to `.env.example`.

> The `VITE_` prefix exposes values to browser JS via `import.meta.env`. The Admin key (`ALGOLIA_SECRET`) is **never** `VITE_`-prefixed.

---

## Step 1 — Install npm packages
```bash
npm install algoliasearch @algolia/autocomplete-js @algolia/autocomplete-theme-classic instantsearch.js
```

---

## Step 2 — `config/scout.php` — Index settings
Add inside `'index-settings'` array (lines 118-123):
```php
'shop_products' => [
    'searchableAttributes' => [
        'unordered(name)', 'unordered(sku)', 'unordered(brand_name)',
        'unordered(category_names)', 'unordered(description)',
    ],
    'customRanking' => ['desc(featured)', 'desc(price)'],
    'attributesForFaceting' => [
        'searchable(brand_name)',
        'searchable(category_names)',
        'filterOnly(brand_id)',
        'filterOnly(category_ids)',
    ],
    'attributesToRetrieve' => [
        'objectID', 'name', 'slug', 'price', 'price_formatted',
        'brand_name', 'category_names', 'image_url', 'url',
    ],
],
'shop_categories' => [
    'searchableAttributes' => ['unordered(name)', 'unordered(description)'],
    'attributesToRetrieve' => ['objectID', 'name', 'slug', 'image_url', 'url'],
],
```

---

## Step 3 — `app/Models/Shop/Product.php` — Customize Scout indexing
Add three methods (prevent N+1 by eager loading in `makeAllSearchableUsing`):

```php
public function makeAllSearchableUsing(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
{
    return $query->with(['brand', 'categories', 'media', 'sharedMedia']);
}

public function shouldBeSearchable(): bool
{
    return $this->is_visible === true;
}

/** @return array<string, mixed> */
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

> `makeAllSearchableUsing` is called by `scout:import` to batch-preload relationships — eliminates N+1 completely. `shouldBeSearchable` prevents invisible products from being indexed on Eloquent save events.

---

## Step 4 — `app/Models/Shop/Category.php` — Add Searchable
Add `use Laravel\Scout\Searchable;` import and the trait. Category already has `InteractsWithMedia`.

```php
use Laravel\Scout\Searchable;
// inside class:
use Searchable;

public function makeAllSearchableUsing(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
{
    return $query->with(['products' => fn ($q) => $q->with(['media'])->where('is_visible', true)->limit(1)]);
}

public function shouldBeSearchable(): bool
{
    return $this->is_visible === true;
}

/** @return array<string, mixed> */
public function toSearchableArray(): array
{
    // Category has its own media; fall back to first product's thumbnail
    $imageUrl = $this->getFirstMediaUrl()
        ?: $this->products->first()?->getMainImageUrl('thumb');

    return [
        'objectID'    => $this->id,
        'name'        => $this->name,
        'slug'        => $this->slug,
        'description' => strip_tags((string) $this->description),
        'image_url'   => $imageUrl ?: null,
        'url'         => route('category.show', $this->slug),
    ];
}
```

---

## Step 5 — `vite.config.js` — Add new entry points
```js
input: [
    'resources/css/app.css',
    'resources/css/shop.css',
    'resources/js/app.js',
    'resources/js/search-autocomplete.js',  // new
    'resources/js/search-results.js',       // new
],
```

---

## Step 6 — `resources/views/partials/shop/header.blade.php`
Add `#autocomplete-container` div between logo and `.header-meta`. The layout already has `@stack('scripts')` so just push from here:

```blade
<header class="header">
    <div class="container header-inner">
        <a href="{{ route('home') }}" class="logo">…</a>

        {{-- Search (filled by search-autocomplete.js) --}}
        <div class="header-search" id="autocomplete-container"></div>

        <div class="header-meta">
            … (existing blocks unchanged) …
            <livewire:shop.cart.cart-counter />
        </div>
    </div>
</header>

@push('scripts')
    @vite('resources/js/search-autocomplete.js')
@endpush
```

> `@stack('scripts')` already exists at line 35 of `shop.blade.php` — no layout changes needed.

---

## Step 7 — `resources/js/search-autocomplete.js` (new file)
Pure client-side autocomplete using `@algolia/autocomplete-js`:
- Two sources: `shop_products` (5 hits) + `shop_categories` (3 hits)
- Product template: thumbnail, name (with highlight), brand, price
- Category template: thumbnail or folder icon, name
- `onSubmit` navigates to `/kereses?q=...` (full results page)
- `detachedMediaQuery: 'none'` — stays inline on mobile (no full-screen overlay)

```js
import algoliasearch from 'algoliasearch/lite';
import { autocomplete, getAlgoliaResults } from '@algolia/autocomplete-js';
import '@algolia/autocomplete-theme-classic';

const searchClient = algoliasearch(
    import.meta.env.VITE_ALGOLIA_APP_ID,
    import.meta.env.VITE_ALGOLIA_SEARCH_KEY
);

autocomplete({
    container: '#autocomplete-container',
    placeholder: 'Termék keresése…',
    openOnFocus: false,
    detachedMediaQuery: 'none',
    onSubmit({ state }) {
        window.location.assign(`/kereses?q=${encodeURIComponent(state.query)}`);
    },
    getSources({ query }) {
        if (!query.trim()) { return []; }
        return [
            {
                sourceId: 'products',
                getItemUrl: ({ item }) => item.url,
                getItems: () => getAlgoliaResults({
                    searchClient,
                    queries: [{ indexName: 'shop_products', query, params: { hitsPerPage: 5 } }],
                }),
                templates: {
                    header: () => `<span class="aa-SourceHeaderTitle">Termékek</span>`,
                    item: ({ item, html }) => html`
                        <a class="search-hit" href="${item.url}">
                            ${item.image_url
                                ? html`<img class="search-hit-thumb" src="${item.image_url}" alt="${item.name}" loading="lazy">`
                                : html`<div class="search-hit-thumb-placeholder">📦</div>`}
                            <div class="search-hit-info">
                                <div class="search-hit-name">${item.name}</div>
                                <div class="search-hit-meta">${item.brand_name ?? ''}</div>
                            </div>
                            <span class="search-hit-price">${item.price_formatted}</span>
                        </a>`,
                    footer: ({ state, html }) =>
                        html`<a class="search-hit search-view-all" href="/kereses?q=${encodeURIComponent(state.query)}">
                            Összes találat →
                        </a>`,
                    noResults: () => 'Nem találtunk terméket.',
                },
            },
            {
                sourceId: 'categories',
                getItemUrl: ({ item }) => item.url,
                getItems: () => getAlgoliaResults({
                    searchClient,
                    queries: [{ indexName: 'shop_categories', query, params: { hitsPerPage: 3 } }],
                }),
                templates: {
                    header: () => `<span class="aa-SourceHeaderTitle">Kategóriák</span>`,
                    item: ({ item, html }) => html`
                        <a class="search-hit" href="${item.url}">
                            ${item.image_url
                                ? html`<img class="search-hit-thumb" src="${item.image_url}" alt="${item.name}" loading="lazy">`
                                : html`<div class="search-hit-thumb-placeholder">🗂</div>`}
                            <div class="search-hit-info">
                                <div class="search-hit-name">${item.name}</div>
                                <div class="search-hit-meta">Kategória</div>
                            </div>
                        </a>`,
                },
            },
        ];
    },
});
```

---

## Step 8 — Route + Controller for `/kereses`

**`routes/web.php`** — add:
```php
use App\Http\Controllers\SearchController;
Route::get('/kereses', [SearchController::class, 'index'])->name('search.index');
```

**`app/Http/Controllers/SearchController.php`** (new, created via `php artisan make:controller SearchController --no-interaction`):
```php
public function index(): \Illuminate\View\View
{
    return view('shop.search', [
        'query' => request()->query('q', ''),
    ]);
}
```
Controller is minimal — all search logic is client-side in instantsearch.js.

---

## Step 9 — `resources/views/shop/search.blade.php` (new file)
Shell view with containers for instantsearch.js widgets:

```blade
<x-layouts.shop>
    <div class="search-page-layout py-6 lg:py-10">
        <aside class="search-facets">
            <div class="search-facet-group">
                <div class="search-facet-title">Gyártó</div>
                <div id="refinement-brand"></div>
            </div>
            <div class="search-facet-group">
                <div class="search-facet-title">Kategória</div>
                <div id="refinement-category"></div>
            </div>
        </aside>
        <div class="search-results-main">
            <div id="search-box"></div>
            <div id="search-stats" class="search-stats"></div>
            <div id="hits" class="search-hits-grid"></div>
            <div id="pagination"></div>
        </div>
    </div>

    @push('scripts')
        @vite('resources/js/search-results.js')
    @endpush
</x-layouts.shop>
```

---

## Step 10 — `resources/js/search-results.js` (new file)
Full instantsearch.js page: SearchBox, Hits (product cards matching existing `.product-card` styles), Stats, RefinementList for brands + categories, Pagination. `routing: true` syncs state to URL (back button works, shareable links).

```js
import algoliasearch from 'algoliasearch/lite';
import instantsearch from 'instantsearch.js';
import { searchBox, hits, pagination, refinementList, stats, configure } from 'instantsearch.js/es/widgets';

const searchClient = algoliasearch(
    import.meta.env.VITE_ALGOLIA_APP_ID,
    import.meta.env.VITE_ALGOLIA_SEARCH_KEY
);

const initialQuery = new URLSearchParams(window.location.search).get('q') ?? '';

const search = instantsearch({
    indexName: 'shop_products',
    searchClient,
    routing: true,
    initialUiState: { shop_products: { query: initialQuery } },
});

search.addWidgets([
    configure({ hitsPerPage: 12 }),

    searchBox({ container: '#search-box', placeholder: 'Termék keresése…', autofocus: true }),

    stats({
        container: '#search-stats',
        templates: {
            text: ({ nbHits, query }) =>
                query ? `${nbHits.toLocaleString('hu-HU')} találat: „${query}"` : `${nbHits.toLocaleString('hu-HU')} termék`,
        },
    }),

    hits({
        container: '#hits',
        templates: {
            item: (hit, { html, components }) => html`
                <article class="product-card">
                    <a href="${hit.url}" class="product-image">
                        ${hit.image_url
                            ? html`<img src="${hit.image_url}" alt="${hit.name}" class="w-full h-full object-cover" loading="lazy">`
                            : html`<span class="text-gray-400 text-sm">Termékkép</span>`}
                    </a>
                    <div class="product-title">
                        <a href="${hit.url}">${components.Highlight({ hit, attribute: 'name' })}</a>
                    </div>
                    <div class="product-meta">${hit.brand_name ?? ''}</div>
                    <div class="product-footer">
                        <div class="product-price">${hit.price_formatted} <small>bruttó</small></div>
                        <a href="${hit.url}" class="btn-outline">Részletek</a>
                    </div>
                </article>`,
            empty: ({ query }, { html }) => html`
                <div class="col-span-full text-center py-12 text-gray-500">
                    Nincs találat: „${query}". Próbáljon más keresési kifejezést.
                </div>`,
        },
    }),

    refinementList({ container: '#refinement-brand', attribute: 'brand_name', limit: 10, showMore: true }),
    refinementList({ container: '#refinement-category', attribute: 'category_names', limit: 10, showMore: true }),

    pagination({
        container: '#pagination',
        padding: 2,
        templates: { previous: '‹ Előző', next: 'Következő ›' },
    }),
]);

search.start();
```

> Hits reuse `.product-card`, `.product-image`, `.product-footer`, `.product-price`, `.btn-outline` classes from existing `shop.css` so results look identical to the category page grid.

---

## Step 11 — `resources/css/shop.css` — Add search styles
Append to end of file. Key additions:
- `.header-search` — flex: 1, max-width 480px, for the search container in header
- Override `@algolia/autocomplete-theme-classic` variables to use shop CSS tokens (`--accent`, `--border-subtle`, etc.)
- `.search-hit` — flex row with 44×44 thumbnail, name, meta, price
- `.search-page-layout` — 240px sidebar + fluid main column grid
- `.search-hits-grid` — 3-col grid (matches existing product grid)
- Responsive: stack on mobile

---

## Step 12 — Run Artisan commands
```bash
php artisan scout:sync-index-settings
php artisan scout:import "App\Models\Shop\Product"
php artisan scout:import "App\Models\Shop\Category"
```

Then rebuild frontend:
```bash
npm run build
```

---

## Step 13 — Tests
Create via `php artisan make:test Feature/ProductSearchableTest --phpunit --no-interaction`:

**`tests/Feature/ProductSearchableTest.php`**:
- `toSearchableArray_returns_expected_keys` — create Product with Brand + Category + media, assert all keys present and brand_name/category_names correct
- `toSearchableArray_includes_formatted_price` — assert price_formatted format matches `X Ft`
- `invisible_product_is_not_searchable` — `is_visible=false` → `shouldBeSearchable()` returns false
- `make_all_searchable_using_eager_loads_relationships` — verify query count is 1 (not N+1) for 3 products with brands/categories

**`tests/Feature/CategorySearchableTest.php`**:
- `to_searchable_array_returns_correct_keys` — visible category, assert objectID/name/slug/url present
- `invisible_category_is_not_searchable` — `is_visible=false` → `shouldBeSearchable()` returns false

**`tests/Feature/SearchControllerTest.php`**:
- `search_page_returns_200` — `GET /kereses` asserts 200
- `search_page_passes_query_to_view` — `GET /kereses?q=test` asserts view receives `query === 'test'`

Run: `php artisan test tests/Feature/ProductSearchableTest.php tests/Feature/CategorySearchableTest.php tests/Feature/SearchControllerTest.php`

---

## Files Changed / Created

| Action | Path |
|--------|------|
| Modify | `app/Models/Shop/Product.php` |
| Modify | `app/Models/Shop/Category.php` |
| Modify | `config/scout.php` |
| Modify | `vite.config.js` |
| Modify | `resources/views/partials/shop/header.blade.php` |
| Modify | `resources/css/shop.css` |
| Modify | `routes/web.php` |
| Modify | `.env` + `.env.example` |
| Create | `app/Http/Controllers/SearchController.php` |
| Create | `resources/js/search-autocomplete.js` |
| Create | `resources/js/search-results.js` |
| Create | `resources/views/shop/search.blade.php` |
| Create | `tests/Feature/ProductSearchableTest.php` |
| Create | `tests/Feature/CategorySearchableTest.php` |
| Create | `tests/Feature/SearchControllerTest.php` |