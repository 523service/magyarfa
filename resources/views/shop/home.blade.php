<x-layouts.shop>
    <div class="grid grid-cols-1 lg:grid-cols-[260px_minmax(0,1fr)] gap-6 py-6 lg:py-10">

        <!-- Sidebar (per-page inclusion, shown below content on mobile) -->
        <div class="order-2 lg:order-1">
            @include('partials.shop.sidebar')
        </div>

        <!-- Main Content (shown first on mobile) -->
        <section class="content flex flex-col gap-5 order-1 lg:order-2">

            @include('partials.shop.hero')
            @include('partials.shop.featured-categories')
            @include('partials.shop.banner')
            @include('partials.shop.product-showcases')
            @include('partials.shop.product-toolbar')
            @include('partials.shop.product-grid', ['products' => $products])

            @if($products->hasPages())
                <div class="pagination-wrapper">
                    {{ $products->links() }}
                </div>
            @endif

        </section>
    </div>
</x-layouts.shop>
