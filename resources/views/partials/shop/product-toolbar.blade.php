@php
    $currentCategorySlug = isset($category) ? $category->slug : null;
    $searchAction = isset($category) ? route('category.show', $category->slug) : route('home');
@endphp
<section class="mfa-filter-bar">
    <div class="mfa-filter-field">
        <label for="toolbar-category">Kategória</label>
        <select
            id="toolbar-category"
            class="select-input"
            onchange="if(this.value) window.location.href = this.value;"
        >
            <option value="{{ route('home') }}#termekek" {{ $currentCategorySlug ? '' : 'selected' }}>Összes kategória</option>
            @foreach($categories ?? [] as $parentCategory)
                <option value="{{ route('category.show', $parentCategory->slug) }}" {{ $currentCategorySlug === $parentCategory->slug ? 'selected' : '' }}>
                    {{ $parentCategory->name }}
                </option>
                @foreach($parentCategory->children as $childCategory)
                    <option value="{{ route('category.show', $childCategory->slug) }}" {{ $currentCategorySlug === $childCategory->slug ? 'selected' : '' }}>
                        &nbsp;&nbsp;{{ $childCategory->name }}
                    </option>
                @endforeach
            @endforeach
        </select>
    </div>

    <form method="GET" action="{{ $searchAction }}" class="mfa-filter-field">
        <label for="toolbar-search">Keresés</label>
        <div class="mfa-filter-input-wrap">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
            <input
                type="search"
                id="toolbar-search"
                name="q"
                value="{{ request('q') }}"
                placeholder="Termék keresése..."
                class="mfa-filter-input"
            >
        </div>
    </form>

    <div class="mfa-filter-field">
        <label for="toolbar-sort">Rendezés</label>
        <select
            id="toolbar-sort"
            class="select-input"
            onchange="toolbarNavigate('sort', this.value)"
        >
            <option value="recommended" {{ request('sort', 'recommended') === 'recommended' ? 'selected' : '' }}>
                Népszerűség szerint
            </option>
            <option value="price_asc" {{ request('sort') === 'price_asc' ? 'selected' : '' }}>
                Ár szerint növekvő
            </option>
            <option value="price_desc" {{ request('sort') === 'price_desc' ? 'selected' : '' }}>
                Ár szerint csökkenő
            </option>
        </select>
    </div>

    @if(isset($availableFilters) && ($availableFilters['brands']->isNotEmpty() || $availableFilters['attributes']->isNotEmpty()))
        <button
            type="button"
            x-data
            @click="$store.filterPanel.open = true; document.body.style.overflow = 'hidden'"
            class="btn-cta-secondary"
            aria-label="Szűrők megnyitása"
        >
            <svg width="15" height="15" viewBox="0 0 15 15" fill="none" aria-hidden="true">
                <path d="M1 3h13M3 7.5h9M5.5 12h4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
            </svg>
            <span>Szűrők</span>
            <span
                x-show="$store.filterPanel.activeCount > 0"
                x-text="$store.filterPanel.activeCount"
                class="filter-open-badge"
            ></span>
        </button>
    @endif
</section>

<script>
function toolbarNavigate(param, value) {
    var url = new URL(window.location.href);
    url.searchParams.set(param, value);
    url.searchParams.delete('page');
    window.location.href = url.toString();
}
</script>

<style>
.filter-open-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 18px;
    height: 18px;
    padding: 0 4px;
    background: var(--color-primary);
    color: white;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 700;
    line-height: 1;
}
</style>
