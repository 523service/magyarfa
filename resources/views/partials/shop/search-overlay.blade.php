{{--
    Search Overlay — Alpine.js-powered header search.
    NOTE: Rendered OUTSIDE <header> in the layout to avoid the backdrop-filter
    stacking context bug (position:fixed descendants are clipped to the header).
    The trigger button lives in header.blade.php and dispatches 'open-search' window event.

    States: default (latest/popular products) | loading | results | empty
    Desktop: wide floating panel below the header
    Mobile:  full-screen overlay with sticky input at the top
--}}
<div
    x-data="shopSearch('{{ route('search.defaults') }}', '{{ route('search.autocomplete') }}', '{{ route('search.index') }}')"
    @keydown.escape.window="isOpen && closePanel()"
    @open-search.window="openPanel()"
>
    {{-- ── Overlay backdrop ─────────────────────────────────────────── --}}
    <div
        class="search-overlay"
        x-show="isOpen"
        x-cloak
        x-transition:enter="so-enter"
        x-transition:enter-start="so-hidden"
        x-transition:enter-end="so-visible"
        x-transition:leave="so-enter"
        x-transition:leave-start="so-visible"
        x-transition:leave-end="so-hidden"
        @click.self="closePanel()"
        role="dialog"
        aria-modal="true"
        aria-label="Termékkeresés"
    >
        {{-- ── Panel ────────────────────────────────────────────────── --}}
        <div class="search-panel" @click.stop>

            {{-- Sticky top row: input + close button --}}
            <div class="search-panel-top">
                <div class="search-top-row">
                    <div class="search-input-wrap">
                        <svg class="search-input-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                            <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
                        </svg>
                        <input
                            x-ref="searchInput"
                            type="text"
                            class="search-input"
                            placeholder="Kezdj el gépelni..."
                            autocomplete="off"
                            spellcheck="false"
                            x-model="query"
                            @input="onQueryInput()"
                            @keydown.enter.prevent="onEnter()"
                        >
                        {{-- Clear button --}}
                        <button
                            type="button"
                            class="search-input-clear"
                            x-show="query.length > 0"
                            @click="query = ''; panelState = 'default'; $refs.searchInput.focus()"
                            aria-label="Törlés"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M18 6 6 18M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <button type="button" class="search-close" @click="closePanel()" aria-label="Bezárás">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M18 6 6 18M6 6l12 12"/></svg>
                    </button>
                </div>
            </div>

            {{-- Scrollable body --}}
            <div class="search-body">

                {{-- ────── DEFAULT STATE: latest + popular products ────── --}}
                <div x-show="panelState === 'default'">
                    <div class="search-grid-desktop">

                        <section class="search-section">
                            <h2 class="search-section-title">Legújabb termékek</h2>
                            <div class="search-list">
                                {{-- Skeleton placeholders while loading --}}
                                <template x-if="defaultLatest.length === 0">
                                    <template x-for="n in [1,2,3]" :key="n">
                                        <div class="search-skeleton-item"></div>
                                    </template>
                                </template>
                                <template x-for="item in defaultLatest" :key="item.url">
                                    <a :href="item.url" class="search-item" @click="closePanel()">
                                        <div class="search-item-thumb-wrap">
                                            <img x-show="item.thumb" :src="item.thumb" :alt="item.title" class="search-item-thumb" loading="lazy">
                                            <div x-show="!item.thumb" class="search-item-thumb-placeholder">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="m3 9 4-4 4 4 4-4 4 4"/><circle cx="8.5" cy="14.5" r="1.5"/></svg>
                                            </div>
                                        </div>
                                        <div class="search-item-content">
                                            <div class="search-item-title" x-text="item.title"></div>
                                            <div class="search-item-meta" x-text="item.meta || 'Új termék'"></div>
                                        </div>
                                    </a>
                                </template>
                            </div>
                        </section>

                        <section class="search-section">
                            <h2 class="search-section-title">Legnépszerűbb termékek</h2>
                            <div class="search-list">
                                <template x-if="defaultPopular.length === 0">
                                    <template x-for="n in [1,2,3]" :key="n">
                                        <div class="search-skeleton-item"></div>
                                    </template>
                                </template>
                                <template x-for="item in defaultPopular" :key="item.url">
                                    <a :href="item.url" class="search-item" @click="closePanel()">
                                        <div class="search-item-thumb-wrap">
                                            <img x-show="item.thumb" :src="item.thumb" :alt="item.title" class="search-item-thumb" loading="lazy">
                                            <div x-show="!item.thumb" class="search-item-thumb-placeholder">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="m3 9 4-4 4 4 4-4 4 4"/><circle cx="8.5" cy="14.5" r="1.5"/></svg>
                                            </div>
                                        </div>
                                        <div class="search-item-content">
                                            <div class="search-item-title" x-text="item.title"></div>
                                            <div class="search-item-meta" x-text="item.meta || 'Kiemelt termék'"></div>
                                        </div>
                                    </a>
                                </template>
                            </div>
                        </section>

                    </div>
                </div>

                {{-- ────── LOADING STATE ──────────────────────────────── --}}
                <div x-show="panelState === 'loading'">
                    <div class="search-list">
                        <template x-for="n in [1,2,3,4]" :key="n">
                            <div class="search-skeleton-item"></div>
                        </template>
                    </div>
                </div>

                {{-- ────── RESULTS STATE: products + categories ────────── --}}
                <div x-show="panelState === 'results'">
                    <div class="search-results-layout">

                        <section class="search-section">
                            <h2 class="search-section-title">Termékek</h2>
                            <div class="search-list">
                                <template x-for="item in products" :key="item.url">
                                    <a :href="item.url" class="search-item" @click="closePanel()">
                                        <div class="search-item-thumb-wrap">
                                            <img x-show="item.thumb" :src="item.thumb" :alt="item.title" class="search-item-thumb" loading="lazy">
                                            <div x-show="!item.thumb" class="search-item-thumb-placeholder">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="m3 9 4-4 4 4 4-4 4 4"/><circle cx="8.5" cy="14.5" r="1.5"/></svg>
                                            </div>
                                        </div>
                                        <div class="search-item-content">
                                            <span class="search-item-badge">Termék</span>
                                            <div class="search-item-title" x-text="item.title"></div>
                                            <div class="search-item-meta" x-text="item.meta || ''"></div>
                                        </div>
                                        <span class="search-item-price" x-text="item.price || ''"></span>
                                    </a>
                                </template>
                            </div>
                            <a
                                :href="resultsUrl + '?q=' + encodeURIComponent(query)"
                                class="search-footer-link"
                                @click="isOpen = false; document.body.style.overflow = ''"
                            >
                                Összes találat megtekintése →
                            </a>
                        </section>

                        <section class="search-section" x-show="categories.length > 0">
                            <h2 class="search-section-title">Kategóriák</h2>
                            <div class="search-list">
                                <template x-for="item in categories" :key="item.url">
                                    <a :href="item.url" class="search-item" @click="closePanel()">
                                        <div class="search-item-thumb-wrap">
                                            <img x-show="item.thumb" :src="item.thumb" :alt="item.title" class="search-item-thumb" loading="lazy">
                                            <div x-show="!item.thumb" class="search-item-thumb-placeholder">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="3"/><path d="M3 9h18M9 21V9"/></svg>
                                            </div>
                                        </div>
                                        <div class="search-item-content">
                                            <span class="search-item-badge search-item-badge--category">Kategória</span>
                                            <div class="search-item-title" x-text="item.title"></div>
                                            <div class="search-item-meta" x-text="item.meta || ''"></div>
                                        </div>
                                    </a>
                                </template>
                            </div>
                        </section>

                    </div>
                </div>

                {{-- ────── EMPTY STATE ─────────────────────────────────── --}}
                <div x-show="panelState === 'empty'" class="search-empty">
                    <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/><path d="M8 11h6"/></svg>
                    <p>Nincs találat erre: <strong x-text="query"></strong></p>
                    <span>Próbálj más keresési kifejezést.</span>
                </div>

            </div>{{-- /.search-body --}}
        </div>{{-- /.search-panel --}}
    </div>{{-- /.search-overlay --}}
</div>{{-- /x-data --}}

@once
<script>
/**
 * shopSearch Alpine component.
 * Registered once via alpine:init so it's safe across Livewire SPA navigations.
 */
document.addEventListener('alpine:init', () => {
    Alpine.data('shopSearch', (defaultsUrl, autocompleteUrl, resultsUrl) => ({
        isOpen: false,
        query: '',
        panelState: 'default',  // 'default' | 'loading' | 'results' | 'empty'
        products: [],
        categories: [],
        defaultLatest: [],
        defaultPopular: [],
        defaultsReady: false,
        timer: null,
        defaultsUrl,
        autocompleteUrl,
        resultsUrl,

        init() {
            // Always release any stale scroll lock from a previous component instance
            // (e.g. when Livewire SPA navigates while the overlay is open).
            document.body.style.overflow = '';

            // Pre-fetch default content in the background so the panel opens instantly.
            this._fetchDefaults();
        },

        openPanel() {
            this.isOpen = true;
            document.body.style.overflow = 'hidden';
            this.$nextTick(() => this.$refs.searchInput?.focus());
        },

        closePanel() {
            this.isOpen = false;
            document.body.style.overflow = '';
            this.query = '';
            this.panelState = 'default';
            this.products = [];
            this.categories = [];
        },

        async _fetchDefaults() {
            if (this.defaultsReady) return;
            try {
                const res = await fetch(this.defaultsUrl);
                if (!res.ok) return;
                const data = await res.json();
                this.defaultLatest  = data.latest  ?? [];
                this.defaultPopular = data.popular ?? [];
                this.defaultsReady = true;
            } catch {
                // Silent fail — panel just shows empty default sections.
            }
        },

        onQueryInput() {
            clearTimeout(this.timer);
            const q = this.query.trim();
            if (!q) {
                this.panelState = 'default';
                this.products   = [];
                this.categories = [];
                return;
            }
            this.panelState = 'loading';
            this.timer = setTimeout(() => this._doSearch(q), 300);
        },

        async _doSearch(q) {
            // Bail if query changed while we were waiting (debounce edge case).
            if (this.query.trim() !== q) return;
            try {
                const url = this.autocompleteUrl + '?q=' + encodeURIComponent(q);
                const res = await fetch(url);
                if (!res.ok) throw new Error('Search request failed');
                const data = await res.json();
                this.products   = data.products   ?? [];
                this.categories = data.categories ?? [];
                this.panelState = (this.products.length + this.categories.length) > 0
                    ? 'results'
                    : 'empty';
            } catch {
                this.panelState = 'empty';
            }
        },

        onEnter() {
            clearTimeout(this.timer);
            const q = this.query.trim();
            if (q) {
                window.location.href = this.resultsUrl + '?q=' + encodeURIComponent(q);
            }
        },
    }));
});
</script>
@endonce
