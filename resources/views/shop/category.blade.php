<x-layouts.shop :title="$category->seo_title ?? $category->name . ' - MagyarSzigetelés.hu'">

    {{-- Filter drawer (fixed overlay, rendered at page level) --}}
    @if(isset($availableFilters) && ($availableFilters['brands']->isNotEmpty() || $availableFilters['attributes']->isNotEmpty()))
        @include('partials.shop.product-filters')
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-[260px_minmax(0,1fr)] gap-6 py-6 lg:py-10">

        <!-- Sidebar (category navigation) -->
        <div class="order-2 lg:order-1">
            @include('partials.shop.sidebar')
        </div>

        <!-- Main Content (shown first on mobile) -->
        <section class="content flex flex-col gap-5 order-1 lg:order-2">

            <!-- Category Header -->
            <div class="category-header">
                <!-- Breadcrumb -->
                <nav class="category-breadcrumb">
                    <a href="{{ route('home') }}">Főoldal</a>
                    <span class="breadcrumb-separator">›</span>
                    @if($category->parent)
                        <a href="{{ route('category.show', $category->parent->slug) }}">{{ $category->parent->name }}</a>
                        <span class="breadcrumb-separator">›</span>
                    @endif
                    <span class="breadcrumb-current">{{ $category->name }}</span>
                </nav>

                <!-- Category Title & Description -->
                <div class="category-hero">
                    <div class="category-hero-content">
                        <div class="category-badge">
                            <span>{{ $products->total() }} termék</span>
                        </div>
                        <h1 class="category-title">{{ $category->name }}</h1>
                        @if($category->description)
                            <div class="category-description">
                                {!! $category->description !!}
                            </div>
                        @endif
                    </div>

                    <!-- Category Image (if available via Spatie Media) -->
                    @if(method_exists($category, 'getFirstMediaUrl') && $category->getFirstMediaUrl('category-images'))
                        <div class="category-hero-image">
                            <img
                                src="{{ $category->getFirstMediaUrl('category-images') }}"
                                alt="{{ $category->name }}"
                            >
                        </div>
                    @endif
                </div>

                <!-- Subcategories (if any) -->
                @if($category->children->count() > 0)
                    <div class="subcategories">
                        <h3 class="subcategories-title">Alkategóriák</h3>
                        <div class="subcategories-grid">
                            @foreach($category->children->where('is_visible', true) as $child)
                                <a href="{{ route('category.show', $child->slug) }}" class="subcategory-card">
                                    @if(method_exists($child, 'getFirstMediaUrl') && $child->getFirstMediaUrl('category-images'))
                                        <div class="subcategory-image">
                                            <img src="{{ $child->getFirstMediaUrl('category-images') }}" alt="{{ $child->name }}">
                                        </div>
                                    @else
                                        <div class="subcategory-icon">
                                            <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                                <path d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                        </div>
                                    @endif
                                    <span class="subcategory-name">{{ $child->name }}</span>
                                    <span class="subcategory-count">{{ $child->products()->count() }} termék</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            <!-- Product Toolbar -->
            @include('partials.shop.product-toolbar')

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
</x-layouts.shop>
