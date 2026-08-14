<section class="product-toolbar">
    <div class="toolbar-left">
        {{ $productCount ?? 0 }} termék
        @if(isset($categoryName))
            egy kategóriában – {{ $categoryName }}
        @endif
    </div>
    <div class="toolbar-right">
        @if(isset($availableFilters) && ($availableFilters['brands']->isNotEmpty() || $availableFilters['attributes']->isNotEmpty()))
            <button
                type="button"
                x-data
                @click="$store.filterPanel.open = true; document.body.style.overflow = 'hidden'"
                class="filter-open-btn"
                aria-label="Szűrők megnyitása"
            >
                <svg width="15" height="15" viewBox="0 0 15 15" fill="none" aria-hidden="true">
                    <path d="M1 3h13M3 7.5h9M5.5 12h4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                </svg>
                Szűrés
                <span
                    x-show="$store.filterPanel.activeCount > 0"
                    x-text="$store.filterPanel.activeCount"
                    class="filter-open-badge"
                ></span>
            </button>
        @endif

        <div class="flex gap-2.5">
            <select
                class="select-input"
                onchange="toolbarNavigate('sort', this.value)"
            >
                <option value="recommended" {{ request('sort', 'recommended') === 'recommended' ? 'selected' : '' }}>
                    Rendezés: Ajánlott
                </option>
                <option value="price_asc" {{ request('sort') === 'price_asc' ? 'selected' : '' }}>
                    Ár szerint növekvő
                </option>
                <option value="price_desc" {{ request('sort') === 'price_desc' ? 'selected' : '' }}>
                    Ár szerint csökkenő
                </option>
            </select>

            <select
                class="select-input"
                onchange="toolbarNavigate('per_page', this.value)"
            >
                <option value="24" {{ (request('per_page', '24') === '24') ? 'selected' : '' }}>24 / oldal</option>
                <option value="48" {{ request('per_page') === '48' ? 'selected' : '' }}>48 / oldal</option>
                <option value="96" {{ request('per_page') === '96' ? 'selected' : '' }}>96 / oldal</option>
            </select>
        </div>

        <script>
        function toolbarNavigate(param, value) {
            var url = new URL(window.location.href);
            url.searchParams.set(param, value);
            url.searchParams.delete('page');
            window.location.href = url.toString();
        }
        </script>
    </div>
</section>

<style>
.filter-open-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    background: transparent;
    border: 1px solid var(--border-subtle);
    border-radius: 999px;
    font-size: 13px;
    font-weight: 600;
    color: var(--text-main);
    cursor: pointer;
    transition: border-color 0.2s ease, color 0.2s ease;
    white-space: nowrap;
}

.filter-open-btn:hover {
    border-color: var(--accent);
    color: var(--accent);
}

.filter-open-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 18px;
    height: 18px;
    padding: 0 4px;
    background: var(--accent);
    color: white;
    border-radius: 999px;
    font-family: 'Courier New', Courier, monospace;
    font-size: 11px;
    font-weight: 700;
    line-height: 1;
}
</style>
