<x-layouts.shop :title="$category->seo_title ?? $category->name . ' - Magyar Fa'">

    {{-- Filter drawer (fixed overlay, rendered at page level) --}}
    @if(isset($availableFilters) && ($availableFilters['brands']->isNotEmpty() || $availableFilters['attributes']->isNotEmpty()))
        @include('partials.shop.product-filters')
    @endif

    <section class="hero hero--page" style="background-image: linear-gradient(90deg, rgba(11,41,24,0.92) 0%, rgba(11,41,24,0.75) 55%, rgba(11,41,24,0.45) 100%), url('{{ asset('img/banner/mfa-banner-01.webp') }}');">
        <div class="hero-content">
            <nav class="mfa-breadcrumb">
                <a href="{{ route('home') }}">Kezdőlap</a>
                <span>›</span>
                @if($category->parent)
                    <a href="{{ route('category.show', $category->parent->slug) }}">{{ $category->parent->name }}</a>
                    <span>›</span>
                @endif
                <span>{{ $category->name }}</span>
            </nav>
            <h1>{{ $category->name }}</h1>
            <p class="hero-lead">
                {{ $category->description ? strip_tags($category->description) : 'Válogasson széles faanyag kínálatunkból.' }}
            </p>
        </div>
    </section>

    <div class="pt-5">
        @include('partials.shop.product-toolbar')
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-[260px_minmax(0,1fr)] gap-6 py-6 lg:py-10">

        <!-- Sidebar (category navigation) -->
        <div class="order-2 lg:order-1">
            @include('partials.shop.sidebar')
        </div>

        <!-- Main Content (shown first on mobile) -->
        <section class="content flex flex-col gap-5 order-1 lg:order-2">

            <!-- Product Grid -->
            @include('partials.shop.product-grid', ['products' => $products])

            <!-- Pagination -->
            @if($products->hasPages())
                <div class="pagination-wrapper">
                    {{ $products->links() }}
                </div>
            @endif

        </section>
    </div>

    @include('partials.shop.benefits')
</x-layouts.shop>
