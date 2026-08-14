<section class="product-grid">
    @forelse($products ?? [] as $product)
        <article class="product-card">
            <a href="{{ route('product.show', $product->slug) }}" class="product-image">
                @php $mainMedia = $product->getMainMedia(); @endphp
                @if($mainMedia)
                    @php
                        $base64svg = $mainMedia->responsive_images['thumb']['base64svg'] ?? null;
                        $srcset = $mainMedia->getSrcset('thumb');
                    @endphp
                    <img
                        src="{{ $base64svg ?: $mainMedia->getUrl('thumb') }}"
                        data-src="{{ $mainMedia->getUrl('thumb') }}"
                        @if($srcset) data-srcset="{{ $srcset }}" @endif
                        sizes="(max-width: 520px) calc(100vw - 2rem), (max-width: 768px) calc(50vw - 2rem), 380px"
                        alt="{{ $product->name }}"
                        class="w-full h-full object-cover lazy"
                    >
                @else
                    <span class="text-gray-400 text-sm">Termékkép</span>
                @endif
            </a>

            <div class="product-title">
                <a href="{{ route('product.show', $product->slug) }}">
                    {{ $product->name ?? 'Termék név' }}
                </a>
            </div>

            @if(isset($product->description) && $product->description)
                <div class="product-meta">
                    {{ Str::limit(strip_tags($product->description), 80) }}
                </div>
            @endif

            <div class="product-footer">
                @php
                    $unitConfig = $product->unitConfig()->with(['baseUnit'])->first();
                    $displayUnit = $unitConfig?->baseUnit?->label_short ?? $product->display_unit ?? null;
                @endphp
                <div class="product-price">
                    {{ number_format($product->getResolvedPrice(), 0, ',', ' ') }} Ft
                    @if($displayUnit)
                        <small>bruttó / {{ $displayUnit }}</small>
                    @endif
                </div>

                <a href="{{ route('product.show', $product->slug) }}" class="btn-outline">
                    Részletek
                </a>
            </div>
        </article>
    @empty
        <div class="col-span-full text-center py-12 text-gray-500">
            Nincs megjeleníthető termék.
        </div>
    @endforelse
</section>
