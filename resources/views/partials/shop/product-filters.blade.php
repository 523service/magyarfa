{{-- Product Filters — Centered Modal Overlay --}}
<div
    x-data="productFilters({{ json_encode($selectedBrands) }}, {{ json_encode($selectedAttributes) }})"
    @keydown.escape.window="closePanel()"
>

    {{-- Backdrop --}}
    <div
        x-show="$store.filterPanel.open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="closePanel()"
        class="filter-backdrop"
        aria-hidden="true"
    ></div>

    {{-- Dialog Panel --}}
    <div
        :class="{ 'is-open': $store.filterPanel.open }"
        class="filter-drawer"
        role="dialog"
        aria-modal="true"
        aria-label="Szűrők"
        x-effect="if ($store.filterPanel.open) $nextTick(() => $refs.searchInput?.focus())"
    >
        {{-- Mobile drag handle --}}
        <div class="filter-drawer-handle" aria-hidden="true"></div>

        {{-- ── Sticky Header ──────────────────────────────── --}}
        <div class="filter-drawer-header">

            {{-- Title row --}}
            <div class="filter-header-title-row">
                <span class="filter-drawer-title">Szűrők</span>
                <button
                    type="button"
                    @click="closePanel()"
                    class="filter-drawer-close"
                    aria-label="Bezárás"
                >
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                        <path d="M3 3L17 17M3 17L17 3" stroke="currentColor" stroke-width="1.5" stroke-linecap="square"/>
                    </svg>
                </button>
            </div>

            {{-- Search input --}}
            <div class="filter-search-wrap">
                <svg class="filter-search-icon" width="15" height="15" viewBox="0 0 15 15" fill="none" aria-hidden="true">
                    <circle cx="6.5" cy="6.5" r="5" stroke="currentColor" stroke-width="1.4"/>
                    <path d="M10.5 10.5L14 14" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
                </svg>
                <input
                    type="search"
                    x-ref="searchInput"
                    x-model="query"
                    placeholder="Keresés a szűrők között..."
                    class="filter-search-input"
                    autocomplete="off"
                >
                <button
                    type="button"
                    x-show="query"
                    @click="query = ''"
                    class="filter-search-clear"
                    aria-label="Keresés törlése"
                >
                    <svg width="14" height="14" viewBox="0 0 14 14" fill="none">
                        <path d="M2 2L12 12M2 12L12 2" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                    </svg>
                </button>
            </div>

            {{-- Quick actions + live counts --}}
            <div class="filter-header-actions">
                <div class="filter-quick-actions">
                    <button
                        type="button"
                        @click="toggleSelectAllVisible()"
                        class="filter-action-btn"
                        x-show="getVisibleCount() > 0"
                    >
                        <span x-text="areAllVisibleSelected() ? 'Összes elvetése' : 'Összes kijelölése'"></span>
                    </button>
                    <button
                        type="button"
                        @click="clearAll()"
                        class="filter-action-btn filter-action-btn--muted"
                        x-show="hasActiveFilters() || query.trim() !== ''"
                    >
                        Törlés
                    </button>
                </div>
                <div class="filter-header-counts">
                    <span class="filter-count-chip" x-show="$store.filterPanel.activeCount > 0">
                        Kijelölve: <strong x-text="$store.filterPanel.activeCount"></strong>
                    </span>
                    <span class="filter-count-chip filter-count-chip--dim" x-show="query.trim() !== ''">
                        Találat: <strong x-text="getVisibleCount()"></strong>
                    </span>
                </div>
            </div>
        </div>{{-- /filter-drawer-header --}}

        {{-- ── Scrollable Body ─────────────────────────────── --}}
        <div class="filter-drawer-body">

            {{-- Active filters bar --}}
            <div class="active-filters" x-show="hasActiveFilters()" x-transition>
                <div class="active-filters-header">
                    <span class="active-filters-label">Aktív szűrők</span>
                    <button
                        type="button"
                        @click="clearAllFilters()"
                        class="clear-all-btn"
                    >
                        <span>Összes törlése</span>
                        <svg width="14" height="14" viewBox="0 0 14 14" fill="none">
                            <path d="M1 1L13 13M1 13L13 1" stroke="currentColor" stroke-width="1.5" stroke-linecap="square"/>
                        </svg>
                    </button>
                </div>
                <div class="active-filters-tags">
                    {{-- Brand filter tags --}}
                    <template x-for="brandId in selectedBrands" :key="'brand-' + brandId">
                        <div class="filter-tag">
                            <span class="filter-tag-label" x-text="getBrandName(brandId)"></span>
                            <button
                                type="button"
                                @click="toggleBrand(brandId)"
                                class="filter-tag-remove"
                                aria-label="Eltávolítás"
                            >
                                <svg width="12" height="12" viewBox="0 0 12 12" fill="none">
                                    <path d="M1 1L11 11M1 11L11 1" stroke="currentColor" stroke-width="1.5" stroke-linecap="square"/>
                                </svg>
                            </button>
                        </div>
                    </template>

                    {{-- Attribute filter tags --}}
                    <template x-for="[attrId, optionIds] in Object.entries(selectedAttributes)" :key="'attr-' + attrId">
                        <template x-for="optionId in optionIds" :key="'option-' + optionId">
                            <div class="filter-tag">
                                <span class="filter-tag-label" x-text="getAttributeOptionName(attrId, optionId)"></span>
                                <button
                                    type="button"
                                    @click="toggleAttribute(attrId, optionId)"
                                    class="filter-tag-remove"
                                    aria-label="Eltávolítás"
                                >
                                    <svg width="12" height="12" viewBox="0 0 12 12" fill="none">
                                        <path d="M1 1L11 11M1 11L11 1" stroke="currentColor" stroke-width="1.5" stroke-linecap="square"/>
                                    </svg>
                                </button>
                            </div>
                        </template>
                    </template>
                </div>
            </div>

            {{-- Filter form --}}
            <form method="GET" action="{{ route('category.show', $category->slug) }}" x-ref="filterForm">

                {{-- Brands section --}}
                @if($availableFilters['brands']->isNotEmpty())
                <div class="filter-section" x-data="{ open: true }" x-show="brandsSectionVisible()">
                    <button
                        type="button"
                        class="filter-section-header"
                        @click="open = !open"
                        :class="{ 'is-open': open }"
                    >
                        <span class="filter-section-title">
                            Gyártók
                            <span class="filter-section-count" x-show="selectedBrands.length > 0" x-text="'(' + selectedBrands.length + ')'"></span>
                        </span>
                        <svg class="filter-section-icon" width="16" height="16" viewBox="0 0 16 16" fill="none">
                            <path d="M4 6L8 10L12 6" stroke="currentColor" stroke-width="1.5" stroke-linecap="square" stroke-linejoin="miter"/>
                        </svg>
                    </button>

                    <div class="filter-section-content" x-show="open" x-collapse>
                        <div class="filter-options">
                            @foreach($availableFilters['brands'] as $brand)
                            <label
                                class="filter-option"
                                data-label="{{ strtolower($brand->name) }}"
                                x-show="matchesQuery($el.dataset.label)"
                            >
                                <input
                                    type="checkbox"
                                    name="brands[]"
                                    value="{{ $brand->id }}"
                                    x-model="selectedBrands"
                                    @change="syncActiveCount()"
                                    class="filter-checkbox"
                                >
                                <span class="filter-checkbox-custom">
                                    <svg class="filter-checkbox-icon" width="10" height="8" viewBox="0 0 10 8" fill="none">
                                        <path d="M1 4L3.5 6.5L9 1" stroke="currentColor" stroke-width="2" stroke-linecap="square" stroke-linejoin="miter"/>
                                    </svg>
                                </span>
                                <span class="filter-option-label">{{ $brand->name }}</span>
                                <span class="filter-option-count">{{ $brand->products_count }}</span>
                            </label>
                            @endforeach
                        </div>
                    </div>
                </div>
                @endif

                {{-- Attribute sections --}}
                @foreach($availableFilters['attributes'] as $attribute)
                <div class="filter-section" x-data="{ open: true }" x-show="attrSectionVisible({{ $attribute->id }})">
                    <button
                        type="button"
                        class="filter-section-header"
                        @click="open = !open"
                        :class="{ 'is-open': open }"
                    >
                        <span class="filter-section-title">
                            {{ $attribute->name }}
                            <span class="filter-section-count" x-show="(selectedAttributes[{{ $attribute->id }}] || []).length > 0" x-text="'(' + (selectedAttributes[{{ $attribute->id }}] || []).length + ')'"></span>
                        </span>
                        <svg class="filter-section-icon" width="16" height="16" viewBox="0 0 16 16" fill="none">
                            <path d="M4 6L8 10L12 6" stroke="currentColor" stroke-width="1.5" stroke-linecap="square" stroke-linejoin="miter"/>
                        </svg>
                    </button>

                    <div class="filter-section-content" x-show="open" x-collapse>
                        <div class="filter-options">
                            @php
                                $filterOptions = $attribute->options->isNotEmpty()
                                    ? $attribute->options
                                    : ($attribute->distinctValues ?? collect());
                            @endphp

                            @foreach($filterOptions as $option)
                            <label
                                class="filter-option"
                                data-label="{{ strtolower($option->value) }}"
                                x-show="matchesQuery($el.dataset.label)"
                            >
                                <input
                                    type="checkbox"
                                    name="attributes[{{ $attribute->id }}][]"
                                    value="{{ $option->id ?? $option->raw_value ?? $option->value }}"
                                    x-model="selectedAttributes[{{ $attribute->id }}]"
                                    @change="syncActiveCount()"
                                    class="filter-checkbox"
                                >
                                <span class="filter-checkbox-custom">
                                    <svg class="filter-checkbox-icon" width="10" height="8" viewBox="0 0 10 8" fill="none">
                                        <path d="M1 4L3.5 6.5L9 1" stroke="currentColor" stroke-width="2" stroke-linecap="square" stroke-linejoin="miter"/>
                                    </svg>
                                </span>
                                <span class="filter-option-label">{{ $option->value }}</span>
                                <span class="filter-option-count">{{ $option->product_values_count }}</span>
                            </label>
                            @endforeach
                        </div>
                    </div>
                </div>
                @endforeach

                {{-- Empty search state --}}
                <div class="filter-no-results" x-show="query.trim() !== '' && getVisibleCount() === 0">
                    <svg width="32" height="32" viewBox="0 0 32 32" fill="none" aria-hidden="true">
                        <circle cx="14" cy="14" r="10" stroke="currentColor" stroke-width="1.5"/>
                        <path d="M22 22L29 29" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                        <path d="M10 14h8M14 10v8" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" opacity=".4"/>
                    </svg>
                    <p>Nincs találat erre: <strong x-text="query"></strong></p>
                    <button type="button" @click="query = ''" class="filter-action-btn">Keresés törlése</button>
                </div>

            </form>
        </div>{{-- /filter-drawer-body --}}

        {{-- ── Sticky Footer ────────────────────────────────── --}}
        <div class="filter-drawer-footer">
            <button
                type="button"
                @click="clearAllFilters()"
                class="filter-clear-btn"
                x-show="hasActiveFilters()"
            >
                Szűrők törlése
            </button>
            <button
                type="button"
                @click="applyFilters()"
                class="filter-apply-btn"
            >
                Találatok megtekintése &rarr;
            </button>
        </div>
    </div>{{-- /filter-drawer --}}
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.store('filterPanel', {
        open: false,
        activeCount: 0
    });

    Alpine.data('productFilters', (initialBrands = [], initialAttributes = {}) => ({
        // ── State ───────────────────────────────────────────
        selectedBrands: Array.isArray(initialBrands) ? initialBrands.map(Number) : [],
        selectedAttributes: typeof initialAttributes === 'object' ?
            Object.fromEntries(
                Object.entries(initialAttributes).map(([k, v]) =>
                    [k, Array.isArray(v) ? v.map(Number) : []]
                )
            ) : {},
        query: '',

        // Server-side data passed in as JSON
        brandsData: @json($availableFilters['brands']),
        attributesData: @json($availableFilters['attributes']),

        // ── Lifecycle ────────────────────────────────────────
        init() {
            // Ensure every attribute has an initialized array
            this.attributesData.forEach(attr => {
                if (!Array.isArray(this.selectedAttributes[attr.id])) {
                    this.selectedAttributes[attr.id] = [];
                }
            });
            // Populate badge count from URL params on page load
            this.syncActiveCount();
        },

        // ── Panel open/close ─────────────────────────────────
        openPanel() {
            Alpine.store('filterPanel').open = true;
            document.body.style.overflow = 'hidden';
        },

        closePanel() {
            Alpine.store('filterPanel').open = false;
            document.body.style.overflow = '';
        },

        // ── Active count ─────────────────────────────────────
        syncActiveCount() {
            const brandCount = this.selectedBrands.length;
            const attrCount = Object.values(this.selectedAttributes).reduce(
                (sum, arr) => sum + (Array.isArray(arr) ? arr.length : 0),
                0
            );
            Alpine.store('filterPanel').activeCount = brandCount + attrCount;
        },

        hasActiveFilters() {
            return this.selectedBrands.length > 0 ||
                   Object.values(this.selectedAttributes).some(arr => arr.length > 0);
        },

        // ── Search helpers ───────────────────────────────────
        matchesQuery(label) {
            if (!this.query.trim()) { return true; }
            return String(label).toLowerCase().includes(this.query.toLowerCase().trim());
        },

        getVisibleBrands() {
            if (!this.query.trim()) { return this.brandsData; }
            const q = this.query.toLowerCase().trim();
            return this.brandsData.filter(b => b.name.toLowerCase().includes(q));
        },

        getVisibleOptionsForAttr(attrId) {
            const attr = this.attributesData.find(a => a.id === Number(attrId));
            if (!attr || !Array.isArray(attr.options)) { return []; }
            if (!this.query.trim()) { return attr.options; }
            const q = this.query.toLowerCase().trim();
            return attr.options.filter(o => String(o.value).toLowerCase().includes(q));
        },

        getVisibleCount() {
            const brandCount = this.getVisibleBrands().length;
            const attrCount = this.attributesData.reduce(
                (s, a) => s + this.getVisibleOptionsForAttr(a.id).length,
                0
            );
            return brandCount + attrCount;
        },

        // ── Section visibility (hide empty sections when searching) ──
        brandsSectionVisible() {
            return this.getVisibleBrands().length > 0;
        },

        attrSectionVisible(attrId) {
            return this.getVisibleOptionsForAttr(attrId).length > 0;
        },

        // ── Select all / Unselect all visible ────────────────
        areAllVisibleSelected() {
            const vBrands = this.getVisibleBrands();
            const vAttrs  = this.attributesData.map(a => ({
                id: a.id,
                options: this.getVisibleOptionsForAttr(a.id)
            }));
            const totalVisible = vBrands.length +
                vAttrs.reduce((s, a) => s + a.options.length, 0);

            if (totalVisible === 0) { return false; }

            const allBrandsSelected = vBrands.every(
                b => this.selectedBrands.includes(b.id)
            );
            const allAttrsSelected = vAttrs.every(a =>
                a.options.every(o => (this.selectedAttributes[a.id] || []).includes(o.id))
            );
            return allBrandsSelected && allAttrsSelected;
        },

        toggleSelectAllVisible() {
            const allSelected = this.areAllVisibleSelected();
            const vBrands = this.getVisibleBrands();

            if (allSelected) {
                // Unselect all visible brands
                vBrands.forEach(b => {
                    const idx = this.selectedBrands.indexOf(b.id);
                    if (idx > -1) { this.selectedBrands.splice(idx, 1); }
                });
                // Unselect all visible attribute options
                this.attributesData.forEach(a => {
                    this.getVisibleOptionsForAttr(a.id).forEach(o => {
                        const idx = (this.selectedAttributes[a.id] || []).indexOf(o.id);
                        if (idx > -1) { this.selectedAttributes[a.id].splice(idx, 1); }
                    });
                });
            } else {
                // Select all visible brands
                vBrands.forEach(b => {
                    if (!this.selectedBrands.includes(b.id)) {
                        this.selectedBrands.push(b.id);
                    }
                });
                // Select all visible attribute options
                this.attributesData.forEach(a => {
                    if (!Array.isArray(this.selectedAttributes[a.id])) {
                        this.selectedAttributes[a.id] = [];
                    }
                    this.getVisibleOptionsForAttr(a.id).forEach(o => {
                        if (!this.selectedAttributes[a.id].includes(o.id)) {
                            this.selectedAttributes[a.id].push(o.id);
                        }
                    });
                });
            }
            this.syncActiveCount();
        },

        // ── Quick action: clear selections + reset search (no submit) ──
        clearAll() {
            this.query = '';
            this.selectedBrands = [];
            this.selectedAttributes = {};
            this.attributesData.forEach(attr => {
                this.selectedAttributes[attr.id] = [];
            });
            this.syncActiveCount();
        },

        // ── Name lookups for active filter tags ──────────────
        getBrandName(brandId) {
            const brand = this.brandsData.find(b => b.id === Number(brandId));
            return brand ? brand.name : '';
        },

        getAttributeOptionName(attrId, optionId) {
            const attr = this.attributesData.find(a => a.id === Number(attrId));
            if (!attr) { return ''; }
            const option = attr.options.find(o => o.id === Number(optionId));
            return option ? `${attr.name}: ${option.value}` : '';
        },

        // ── Toggle individual items ───────────────────────────
        toggleBrand(brandId) {
            const index = this.selectedBrands.indexOf(Number(brandId));
            if (index > -1) {
                this.selectedBrands.splice(index, 1);
            } else {
                this.selectedBrands.push(Number(brandId));
            }
            this.syncActiveCount();
        },

        toggleAttribute(attrId, optionId) {
            if (!Array.isArray(this.selectedAttributes[attrId])) {
                this.selectedAttributes[attrId] = [];
            }
            const index = this.selectedAttributes[attrId].indexOf(Number(optionId));
            if (index > -1) {
                this.selectedAttributes[attrId].splice(index, 1);
            } else {
                this.selectedAttributes[attrId].push(Number(optionId));
            }
            this.syncActiveCount();
        },

        // ── Footer: clear + submit ────────────────────────────
        clearAllFilters() {
            this.selectedBrands = [];
            this.selectedAttributes = {};
            this.attributesData.forEach(attr => {
                this.selectedAttributes[attr.id] = [];
            });
            this.syncActiveCount();
            this.applyFilters();
        },

        applyFilters() {
            this.$nextTick(() => {
                this.$refs.filterForm.submit();
            });
        }
    }));
});
</script>

<style>
/* ── Backdrop ───────────────────────────────────────── */
.filter-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.45);
    z-index: 40;
    cursor: pointer;
}

/* ── Dialog — mobile: bottom sheet ─────────────────── */
.filter-drawer {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    z-index: 50;
    max-height: 90vh;
    background: var(--bg, #fff);
    border-radius: 20px 20px 0 0;
    transform: translateY(100%);
    transition: transform 0.32s cubic-bezier(0.4, 0, 0.2, 1);
    display: flex;
    flex-direction: column;
    box-shadow: 0 -8px 40px rgba(0, 0, 0, 0.18);
}

.filter-drawer.is-open {
    transform: translateY(0);
}

/* ── Dialog — tablet & desktop: centered modal ──────── */
@media (min-width: 640px) {
    .filter-drawer {
        /* Center on screen */
        top: 50%;
        left: 50%;
        right: auto;
        bottom: auto;
        /* Responsive width: almost full on tablet, capped on desktop */
        width: calc(100% - 2rem);
        max-width: 800px;
        max-height: 85vh;
        border-radius: 12px;
        /* Hidden state: slightly raised + transparent */
        transform: translate(-50%, -48%) scale(0.97);
        opacity: 0;
        pointer-events: none;
        transition:
            transform 0.25s cubic-bezier(0.4, 0, 0.2, 1),
            opacity   0.2s  ease;
        box-shadow: 0 24px 64px rgba(0, 0, 0, 0.22), 0 4px 16px rgba(0, 0, 0, 0.08);
    }

    .filter-drawer.is-open {
        transform: translate(-50%, -50%) scale(1);
        opacity: 1;
        pointer-events: auto;
    }

    /* Hide mobile handle on tablet+ */
    .filter-drawer-handle {
        display: none;
    }
}

/* ── Mobile drag handle ─────────────────────────────── */
.filter-drawer-handle {
    width: 40px;
    height: 4px;
    background: var(--border-subtle);
    border-radius: 2px;
    margin: 14px auto 0;
    flex-shrink: 0;
}

/* ── Sticky Header ──────────────────────────────────── */
.filter-drawer-header {
    padding: 1rem 1.25rem 0.875rem;
    border-bottom: 1px solid var(--border-subtle);
    flex-shrink: 0;
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

/* Title row: "Szűrők" + close button */
.filter-header-title-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.filter-drawer-title {
    font-size: 1.0625rem;
    font-weight: 700;
    color: var(--text-main);
    letter-spacing: -0.01em;
}

.filter-drawer-close {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    background: transparent;
    border: 1px solid var(--border-subtle);
    border-radius: 6px;
    color: var(--text-muted);
    cursor: pointer;
    transition: all 0.15s ease;
    padding: 0;
    flex-shrink: 0;
}

.filter-drawer-close:hover {
    background: color-mix(in srgb, var(--text-main) 6%, transparent);
    color: var(--text-main);
    border-color: var(--text-main);
}

/* ── Search input ───────────────────────────────────── */
.filter-search-wrap {
    position: relative;
    display: flex;
    align-items: center;
}

.filter-search-icon {
    position: absolute;
    left: 10px;
    color: var(--text-muted);
    pointer-events: none;
    flex-shrink: 0;
}

.filter-search-input {
    width: 100%;
    padding: 0.5rem 2.25rem 0.5rem 2rem;
    border: 1px solid var(--border-subtle);
    border-radius: 6px;
    font-size: 0.875rem;
    color: var(--text-main);
    background: color-mix(in srgb, var(--bg, #fff) 60%, var(--border-subtle) 40%);
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
    /* Remove browser-native search clear button */
    -webkit-appearance: none;
    appearance: none;
}

.filter-search-input::-webkit-search-cancel-button {
    -webkit-appearance: none;
}

.filter-search-input::placeholder {
    color: var(--text-muted);
}

.filter-search-input:focus {
    outline: none;
    border-color: var(--accent);
    box-shadow: 0 0 0 3px color-mix(in srgb, var(--accent) 15%, transparent);
    background: var(--bg, #fff);
}

.filter-search-clear {
    position: absolute;
    right: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 20px;
    height: 20px;
    background: var(--border-subtle);
    border: none;
    border-radius: 50%;
    color: var(--text-muted);
    cursor: pointer;
    padding: 0;
    transition: background 0.15s ease, color 0.15s ease;
}

.filter-search-clear:hover {
    background: var(--text-muted);
    color: #fff;
}

/* ── Quick actions + counts row ─────────────────────── */
.filter-header-actions {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
    flex-wrap: wrap;
    min-height: 28px;
}

.filter-quick-actions {
    display: flex;
    align-items: center;
    gap: 0.375rem;
}

.filter-action-btn {
    padding: 0.3125rem 0.75rem;
    background: transparent;
    border: 1px solid var(--border-subtle);
    border-radius: 999px;
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--text-main);
    cursor: pointer;
    transition: all 0.15s ease;
    white-space: nowrap;
    line-height: 1.4;
}

.filter-action-btn:hover {
    border-color: var(--accent);
    color: var(--accent);
}

.filter-action-btn--muted {
    color: var(--text-muted);
}

.filter-action-btn--muted:hover {
    color: var(--text-main);
    border-color: var(--text-main);
}

.filter-header-counts {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.filter-count-chip {
    font-family: 'Courier New', Courier, monospace;
    font-size: 0.6875rem;
    font-weight: 600;
    background: color-mix(in srgb, var(--accent) 10%, transparent);
    color: var(--accent);
    border: 1px solid color-mix(in srgb, var(--accent) 25%, transparent);
    padding: 0.1875rem 0.5rem;
    border-radius: 999px;
    white-space: nowrap;
}

.filter-count-chip--dim {
    background: color-mix(in srgb, var(--text-muted) 8%, transparent);
    color: var(--text-muted);
    border-color: color-mix(in srgb, var(--text-muted) 20%, transparent);
}

/* ── Scrollable Body ────────────────────────────────── */
.filter-drawer-body {
    flex: 1;
    overflow-y: auto;
    padding: 1rem 1.25rem;
    display: flex;
    flex-direction: column;
    gap: 1rem;
    overscroll-behavior: contain;
}

/* ── Sticky Footer ──────────────────────────────────── */
.filter-drawer-footer {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.875rem 1.25rem;
    border-top: 1px solid var(--border-subtle);
    flex-shrink: 0;
}

.filter-clear-btn {
    flex: 1;
    padding: 0.625rem 1rem;
    background: transparent;
    border: 1px solid var(--border-subtle);
    border-radius: 4px;
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--text-muted);
    cursor: pointer;
    transition: all 0.15s ease;
    text-align: center;
    white-space: nowrap;
}

.filter-clear-btn:hover {
    border-color: var(--text-main);
    color: var(--text-main);
}

.filter-apply-btn {
    flex: 2;
    padding: 0.625rem 1rem;
    background: var(--accent);
    border: none;
    border-radius: 4px;
    font-size: 0.875rem;
    font-weight: 700;
    color: white;
    cursor: pointer;
    transition: all 0.15s ease;
    text-align: center;
    white-space: nowrap;
}

.filter-apply-btn:hover {
    background: color-mix(in srgb, var(--accent) 85%, black);
    transform: translateY(-1px);
}

/* ── Empty search state ─────────────────────────────── */
.filter-no-results {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.75rem;
    padding: 2.5rem 1rem;
    text-align: center;
    color: var(--text-muted);
}

.filter-no-results p {
    font-size: 0.9375rem;
    font-weight: 500;
    margin: 0;
}

.filter-no-results strong {
    color: var(--text-main);
}

/* ── Active Filters Bar ─────────────────────────────── */
.active-filters {
    background: linear-gradient(135deg, var(--accent) 0%, color-mix(in srgb, var(--accent) 85%, black) 100%);
    border: 1px solid color-mix(in srgb, var(--accent) 80%, transparent);
    border-radius: 4px;
    padding: 1rem;
    position: relative;
    overflow: hidden;
}

.active-filters::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0; bottom: 0;
    background:
        repeating-linear-gradient(
            0deg,
            transparent, transparent 2px,
            rgba(255,255,255,.03) 2px, rgba(255,255,255,.03) 4px
        ),
        repeating-linear-gradient(
            90deg,
            transparent, transparent 2px,
            rgba(255,255,255,.03) 2px, rgba(255,255,255,.03) 4px
        );
    pointer-events: none;
}

.active-filters-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.75rem;
    position: relative;
    z-index: 1;
}

.active-filters-label {
    font-family: 'Courier New', Courier, monospace;
    font-size: 0.6875rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: white;
}

.clear-all-btn {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    background: rgba(255,255,255,.15);
    border: 1px solid rgba(255,255,255,.25);
    color: white;
    padding: 0.375rem 0.625rem;
    font-size: 0.75rem;
    font-weight: 600;
    border-radius: 2px;
    cursor: pointer;
    transition: all 0.15s ease;
}

.clear-all-btn:hover {
    background: rgba(255,255,255,.25);
    border-color: rgba(255,255,255,.4);
    transform: translateY(-1px);
}

.active-filters-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    position: relative;
    z-index: 1;
}

.filter-tag {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    background: rgba(255,255,255,.95);
    border: 1px solid rgba(0,0,0,.1);
    padding: 0.375rem 0.625rem;
    font-size: 0.8125rem;
    font-weight: 500;
    color: var(--text-main);
    border-radius: 2px;
    position: relative;
    transition: all 0.2s ease;
}

.filter-tag::before {
    content: '';
    position: absolute;
    left: 0; top: 0; bottom: 0;
    width: 3px;
    background: var(--accent);
}

.filter-tag:hover {
    transform: translateX(2px);
    box-shadow: 0 2px 4px rgba(0,0,0,.1);
}

.filter-tag-label {
    line-height: 1.2;
}

.filter-tag-remove {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 18px;
    height: 18px;
    background: transparent;
    border: none;
    color: var(--text-muted);
    cursor: pointer;
    border-radius: 2px;
    transition: all 0.15s ease;
    padding: 0;
}

.filter-tag-remove:hover {
    background: rgba(0,0,0,.08);
    color: var(--text-main);
}

/* ── Filter Sections ────────────────────────────────── */
.filter-section {
    border: 1px solid var(--border-subtle);
    border-radius: 4px;
    background: var(--bg);
    overflow: hidden;
    position: relative;
}

.filter-section::before {
    content: '';
    position: absolute;
    top: 0; left: 0;
    width: 3px;
    height: 100%;
    background: var(--accent);
    opacity: 0;
    transition: opacity 0.2s ease;
}

.filter-section:has(.filter-checkbox:checked)::before {
    opacity: 1;
}

.filter-section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    width: 100%;
    padding: 0.75rem 1rem;
    background: transparent;
    border: none;
    cursor: pointer;
    transition: background 0.15s ease;
    border-bottom: 1px solid transparent;
}

.filter-section-header:hover {
    background: color-mix(in srgb, var(--accent) 3%, transparent);
}

.filter-section-header.is-open {
    border-bottom-color: var(--border-subtle);
}

.filter-section-title {
    font-size: 0.9375rem;
    font-weight: 600;
    color: var(--text-main);
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.filter-section-count {
    font-family: 'Courier New', Courier, monospace;
    font-size: 0.8125rem;
    font-weight: 700;
    color: var(--accent);
}

.filter-section-icon {
    color: var(--text-muted);
    transition: transform 0.25s ease;
    flex-shrink: 0;
}

.filter-section-header.is-open .filter-section-icon {
    transform: rotate(180deg);
}

.filter-section-content {
    padding: 0.875rem 1rem 1rem;
}

/* ── Options grid ───────────────────────────────────── */
.filter-options {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 6px;
}

@media (min-width: 640px) {
    .filter-options {
        grid-template-columns: repeat(3, 1fr);
        gap: 8px;
    }
}

/* ── Option card (grid cell) ────────────────────────── */
.filter-option {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
    padding: 0.5rem 0.625rem;
    border: 1.5px solid var(--border-subtle);
    border-radius: 6px;
    background: var(--bg, #fff);
    transition: border-color 0.15s ease, background 0.15s ease, box-shadow 0.15s ease;
    position: relative;
}

.filter-option:hover {
    border-color: color-mix(in srgb, var(--accent) 50%, var(--border-subtle));
    background: color-mix(in srgb, var(--accent) 4%, var(--bg, #fff));
}

.filter-option:has(.filter-checkbox:checked) {
    border-color: var(--accent);
    background: color-mix(in srgb, var(--accent) 8%, var(--bg, #fff));
    box-shadow: 0 0 0 1px color-mix(in srgb, var(--accent) 30%, transparent);
}

/* ── Custom checkbox ────────────────────────────────── */
.filter-checkbox {
    position: absolute;
    opacity: 0;
    pointer-events: none;
}

.filter-checkbox-custom {
    flex-shrink: 0;
    width: 16px;
    height: 16px;
    border: 1.5px solid var(--border-subtle);
    border-radius: 3px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: white;
    transition: all 0.15s ease;
    position: relative;
}

.filter-checkbox-custom::before {
    content: '';
    position: absolute;
    inset: 0;
    border-radius: 1px;
    background: var(--accent);
    opacity: 0;
    transform: scale(0.6);
    transition: all 0.15s ease;
}

.filter-checkbox:checked + .filter-checkbox-custom::before {
    opacity: 1;
    transform: scale(1);
}

.filter-checkbox-icon {
    color: white;
    opacity: 0;
    transform: scale(0.5);
    transition: all 0.15s ease;
    position: relative;
    z-index: 1;
}

.filter-checkbox:checked + .filter-checkbox-custom .filter-checkbox-icon {
    opacity: 1;
    transform: scale(1);
}

.filter-option:hover .filter-checkbox-custom {
    border-color: var(--accent);
    box-shadow: 0 0 0 2px color-mix(in srgb, var(--accent) 12%, transparent);
}

.filter-option-label {
    flex: 1;
    font-size: 0.8125rem;
    font-weight: 500;
    color: var(--text-main);
    line-height: 1.3;
    /* Truncate very long labels */
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
    min-width: 0;
}

.filter-option-count {
    font-family: 'Courier New', Courier, monospace;
    font-size: 0.6875rem;
    font-weight: 700;
    color: var(--text-muted);
    background: color-mix(in srgb, var(--text-muted) 8%, transparent);
    padding: 0.125rem 0.375rem;
    border-radius: 3px;
    min-width: 22px;
    text-align: center;
    flex-shrink: 0;
}

.filter-option:has(.filter-checkbox:checked) .filter-option-count {
    background: var(--accent);
    color: white;
}
</style>
