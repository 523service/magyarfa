<x-layouts.shop :title="($query ? 'Keresés: ' . $query : 'Termékkeresés') . ' - MagyarSzigetelés.hu'">

    <div class="grid grid-cols-1 lg:grid-cols-[260px_minmax(0,1fr)] gap-6 py-6 lg:py-10">

        {{-- Sidebar: Algolia refinement filters --}}
        <div class="order-2 lg:order-1">
            <aside class="sidebar">
                <div class="sidebar-card">
                    <h2 class="sidebar-title">Gyártó</h2>
                    <div id="refinement-brand"></div>
                </div>
                <div class="sidebar-card">
                    <h2 class="sidebar-title">Kategória</h2>
                    <div id="refinement-category"></div>
                </div>
            </aside>
        </div>

        {{-- Main content --}}
        <section class="flex flex-col gap-5 order-1 lg:order-2">

            {{-- Header --}}
            <div class="category-header">
                <nav class="category-breadcrumb">
                    <a href="{{ route('home') }}">Főoldal</a>
                    <span class="breadcrumb-separator">›</span>
                    <span class="breadcrumb-current">Keresési találatok</span>
                </nav>

                <div class="category-hero">
                    <div class="category-hero-content">
                        <div class="category-badge">
                            <div id="search-stats"></div>
                        </div>
                        <h1 class="category-title">
                            @if($query)
                                Keresés: <em class="font-normal" style="color: var(--accent-dark)">{{ $query }}</em>
                            @else
                                Termékkeresés
                            @endif
                        </h1>
                    </div>
                </div>
            </div>

            {{-- InstantSearch search box --}}
            <div id="search-box"></div>

            {{-- Product hits --}}
            <div id="hits"></div>

            {{-- Pagination --}}
            <div id="pagination"></div>

        </section>
    </div>

    @push('scripts')
        @vite('resources/js/search-results.js')
    @endpush
</x-layouts.shop>
