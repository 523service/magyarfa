# 0301 — Filter Drawer Modal Plan

## Context

The product filter is currently embedded in a 260px sidebar column on `category.blade.php`. The goal is to replace it with a **responsive drawer overlay** triggered by a "Szűrés" button in the toolbar — on all screen sizes. This frees up horizontal space for the product grid and provides a more modern, mobile-native UX pattern.

**Decisions:**
- Modal on all screen sizes (sidebar filter removed entirely)
- Drawer style: slides from **left on desktop**, slides **up from bottom on mobile** (bottom sheet)
- "Szűrés" button shows **active filter count badge**
- Implementation: **Alpine.js only** (no new Livewire component)

---

## Architecture: Alpine.store for Cross-Component State

The toolbar button and filter panel are separate Blade partials. They communicate via an `Alpine.store` — decoupled, no Blade restructuring needed.

```
Alpine.store('filterPanel', { open: false, activeCount: 0 })
  ↑ written by productFilters() Alpine component
  ↑ read by toolbar button (badge + open trigger)
```

---

## Files to Change

| File | Change |
|------|--------|
| `resources/views/partials/shop/product-filters.blade.php` | Full rewrite as drawer overlay |
| `resources/views/partials/shop/product-toolbar.blade.php` | Add Szűrés button + count badge |
| `resources/views/shop/category.blade.php` | Move filter include out of sidebar to page level |

**Not changed:** `CategoryController.php`, `shop.css`, any Livewire files.

---

## 1. `product-filters.blade.php` — Drawer Rewrite

### Visual Structure
```
<div x-data="productFilters(...)">

  <!-- Backdrop: fixed inset-0 bg-black/50 z-40 -->
  <!-- x-show="$store.filterPanel.open" + opacity transition -->

  <!-- Drawer panel: .filter-drawer, fixed, z-50 -->
  <!-- :class="{ 'is-open': $store.filterPanel.open }" -->
    <!-- Mobile drag handle (decorative pill) -->
    <!-- Header: "Szűrők" + ✕ close button -->
    <!-- Active filters bar (existing markup reused) -->
    <!-- Scrollable filter sections (brands + attributes, existing markup) -->
    <!-- Sticky footer: [Szűrők törlése]  [Találatok megtekintése →] -->

  <!-- Hidden form x-ref="filterForm" -->

</div>
```

### CSS (embedded `<style>` block — consistent with existing pattern)

```css
/* Mobile: bottom sheet */
.filter-drawer {
  position: fixed; bottom: 0; left: 0; right: 0; z-index: 50;
  max-height: 85vh; overflow-y: auto;
  background: var(--card-bg); border-radius: 16px 16px 0 0;
  transform: translateY(100%);
  transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  display: flex; flex-direction: column;
}
.filter-drawer.is-open { transform: translateY(0); }

/* Desktop: left side drawer */
@media (min-width: 768px) {
  .filter-drawer {
    top: 0; bottom: 0; left: 0; right: auto;
    width: 340px; max-height: 100vh;
    border-radius: 0; transform: translateX(-100%);
  }
  .filter-drawer.is-open { transform: translateX(0); }
}
```

Visibility controlled via CSS transforms only (element always in DOM).
Backdrop: `x-show` + Alpine opacity transitions (same pattern as cart modal).

### Alpine.js Extensions

New additions to `productFilters()` in the `<script>` block:

```javascript
// In alpine:init, before Alpine.data():
Alpine.store('filterPanel', { open: false, activeCount: 0 });

// New methods inside productFilters():
openPanel()  → store.open = true  + body overflow lock
closePanel() → store.open = false + overflow unlock
syncActiveCount() → counts selectedBrands.length + sum of selectedAttributes arrays
                    → writes to store.activeCount

init() { ...existing...; this.syncActiveCount(); }  // populate badge on load

// Add this.syncActiveCount() calls at end of:
toggleBrand(), toggleAttribute(), clearAllFilters()
```

Escape key: `@keydown.escape.window="closePanel()"` on root element.

---

## 2. `product-toolbar.blade.php` — Szűrés Button

Added to `toolbar-right`, before the sort/per-page form:

```blade
@if(isset($availableFilters) && ...)
<button type="button" x-data
    @click="$store.filterPanel.open = true; document.body.style.overflow = 'hidden'"
    class="filter-open-btn">
    <svg><!-- sliders/filter icon --></svg>
    Szűrés
    <span x-show="$store.filterPanel.activeCount > 0"
          x-text="$store.filterPanel.activeCount"
          class="filter-open-badge"></span>
</button>
@endif
```

CSS (embedded `<style>`): pill shape matching `.select-input` height, accent-colored badge circle.

---

## 3. `category.blade.php` — Restructure

Move the filter include **outside and above** the layout grid (it's `position: fixed`, no layout impact):

```blade
{{-- Filter drawer overlay --}}
@if(isset($availableFilters) && ...)
    @include('partials.shop.product-filters')
@endif

<div class="grid grid-cols-1 lg:grid-cols-[260px_minmax(0,1fr)] gap-6 py-6 lg:py-10">
    <div class="order-2 lg:order-1">
        @include('partials.shop.sidebar')   {{-- category nav only --}}
    </div>
    <section class="content ...">
        ...
        @include('partials.shop.product-toolbar')
        ...
    </section>
</div>
```

The sidebar column stays for category navigation (consistent with home page).

---

## UX Behaviour

| Interaction | Result |
|---|---|
| Click "Szűrés" button | Drawer opens, body scroll locked |
| Click backdrop | Drawer closes, scroll unlocked |
| Press Escape | Drawer closes |
| Click ✕ in drawer header | Drawer closes |
| Check/uncheck a filter | Auto-submits form (existing behaviour), badge count updates |
| Click "Szűrők törlése" | Clears all, auto-submits |
| Click "Találatok megtekintése" | Submits form |
| Pre-selected filters (URL params) | Badge shows correct count on page load |

---

## Verification Checklist

- [ ] `npm run build` completes without errors
- [ ] Category page: no sidebar filter visible, "Szűrés" button in toolbar
- [ ] Drawer opens/closes correctly on desktop (left slide) and mobile (bottom sheet)
- [ ] Count badge reflects active filters; updates when filters change
- [ ] Active filters tags render inside drawer; ✕ removes them
- [ ] Backdrop click and Escape key close drawer
- [ ] Page-load with `?brands[]=1` → badge shows "1"
- [ ] `php artisan test tests/Feature/ExampleTest.php` passes
