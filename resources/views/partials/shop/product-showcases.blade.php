{{-- Homepage Showcase Section --}}
@if(isset($homepageProducts) && $homepageProducts->count() > 0)
<section class="showcase-section" id="kiemelt-ajanlat">
    <div class="showcase-header">
        <h2 class="showcase-title">Kiemelt ajánlataink</h2>
        <p class="showcase-subtitle">Gondosan válogatott termékek</p>
    </div>
    <div class="showcase-grid">
        @foreach($homepageProducts as $product)
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
                        sizes="(max-width: 520px) calc(100vw - 2rem), (max-width: 768px) calc(50vw - 2rem), 280px"
                        alt="{{ $product->name }}"
                        class="w-full h-full object-cover lazy"
                    >
                @else
                    <span class="text-gray-400 text-sm">Termékkép</span>
                @endif
            </a>
            <div class="product-title">
                <a href="{{ route('product.show', $product->slug) }}">{{ $product->name }}</a>
            </div>
            <div class="product-footer">
                @php
                    $unitConfig = $product->unitConfig()->with(['baseUnit'])->first();
                    $displayUnit = $unitConfig?->baseUnit?->label_short ?? $product->display_unit ?? null;
                @endphp
                <div class="product-price">
                    {{ number_format($product->getResolvedPrice(), 0, ',', ' ') }} Ft
                    <small>bruttó{{ $displayUnit ? ' / ' . $displayUnit : '' }}</small>
                </div>
                <a href="{{ route('product.show', $product->slug) }}" class="btn-outline">Részletek</a>
            </div>
        </article>
        @endforeach
    </div>
</section>
@endif

{{-- Featured Products Section --}}
@if(isset($featuredProducts) && $featuredProducts->count() > 0)
<section class="showcase-section" id="kiemelt-termekek">
    <div class="showcase-header">
        <h2 class="showcase-title">Kiemelt termékek</h2>
        <p class="showcase-subtitle">Legnépszerűbb termékeink</p>
    </div>
    <div class="showcase-grid">
        @foreach($featuredProducts as $product)
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
                        sizes="(max-width: 520px) calc(100vw - 2rem), (max-width: 768px) calc(50vw - 2rem), 280px"
                        alt="{{ $product->name }}"
                        class="w-full h-full object-cover lazy"
                    >
                @else
                    <span class="text-gray-400 text-sm">Termékkép</span>
                @endif
                <span class="product-badge">Kiemelt</span>
            </a>
            <div class="product-title">
                <a href="{{ route('product.show', $product->slug) }}">{{ $product->name }}</a>
            </div>
            <div class="product-footer">
                @php
                    $unitConfig = $product->unitConfig()->with(['baseUnit'])->first();
                    $displayUnit = $unitConfig?->baseUnit?->label_short ?? $product->display_unit ?? null;
                @endphp
                <div class="product-price">
                    {{ number_format($product->getResolvedPrice(), 0, ',', ' ') }} Ft
                    <small>bruttó{{ $displayUnit ? ' / ' . $displayUnit : '' }}</small>
                </div>
                <a href="{{ route('product.show', $product->slug) }}" class="btn-outline">Részletek</a>
            </div>
        </article>
        @endforeach
    </div>
</section>
@endif

{{-- Sale Products Section --}}
@if(isset($saleProducts) && $saleProducts->count() > 0)
<section class="showcase-section" id="akcio">
    <div class="showcase-header">
        <h2 class="showcase-title">Akciós termékek</h2>
        <p class="showcase-subtitle">Ne hagyd ki ezeket az ajánlatokat!</p>
    </div>
    <div class="showcase-grid">
        @foreach($saleProducts as $product)
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
                        sizes="(max-width: 520px) calc(100vw - 2rem), (max-width: 768px) calc(50vw - 2rem), 280px"
                        alt="{{ $product->name }}"
                        class="w-full h-full object-cover lazy"
                    >
                @else
                    <span class="text-gray-400 text-sm">Termékkép</span>
                @endif
                <span class="product-badge product-badge--sale">Akció</span>
            </a>
            <div class="product-title">
                <a href="{{ route('product.show', $product->slug) }}">{{ $product->name }}</a>
            </div>
            <div class="product-footer">
                @php
                    $unitConfig = $product->unitConfig()->with(['baseUnit'])->first();
                    $displayUnit = $unitConfig?->baseUnit?->label_short ?? $product->display_unit ?? null;
                @endphp
                <div class="product-price">
                    {{ number_format($product->getResolvedPrice(), 0, ',', ' ') }} Ft
                    <small>bruttó{{ $displayUnit ? ' / ' . $displayUnit : '' }}</small>
                    @if($product->old_price && $product->old_price > $product->price)
                        <s class="showcase-old-price">{{ number_format($product->old_price, 0, ',', ' ') }} Ft</s>
                    @endif
                </div>
                <a href="{{ route('product.show', $product->slug) }}" class="btn-outline">Részletek</a>
            </div>
        </article>
        @endforeach
    </div>
</section>
@endif
