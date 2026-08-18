{{-- Homepage "Népszerű termékeink" section --}}
@if(isset($homepageProducts) && $homepageProducts->count() > 0)
<section class="mfa-section" id="nepszeru-termekek">
    <h2 class="mfa-section-title">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 2 3 9l9 5 9-5-9-7Z"/><path d="m3 14 9 5 9-5"/></svg>
        <span>Népszerű termékeink</span>
    </h2>

    <div class="mfa-products">
        @foreach($homepageProducts as $product)
            @php
                $mainMedia = $product->getMainMedia();
                $unitConfig = $product->unitConfig()->with(['baseUnit'])->first();
                $primaryUnit = $product->units->firstWhere('pivot.is_primary', true) ?? $product->units->first();
                $displayUnit = $unitConfig?->baseUnit?->label_short ?? $primaryUnit?->label_short ?? $product->display_unit ?? null;
            @endphp
            <article class="mfa-product-card">
                <a href="{{ route('product.show', $product->slug) }}" class="mfa-product-image">
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
                        <span class="mfa-product-image-placeholder" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="7" width="18" height="13" rx="1"/><path d="M3 11h18M8 7V4h8v3"/></svg>
                            <span>Termékkép hamarosan</span>
                        </span>
                    @endif
                </a>
                <div class="mfa-product-body">
                    <a href="{{ route('product.show', $product->slug) }}" class="mfa-product-title">{{ $product->name }}</a>
                    @if($product->description)
                        <p class="mfa-product-spec">{{ \Illuminate\Support\Str::limit(strip_tags($product->description), 60) }}</p>
                    @endif
                    <div class="mfa-product-footer">
                        <div class="mfa-product-price">
                            {{ number_format($product->getResolvedPrice(), 0, ',', ' ') }} Ft
                            <small>bruttó{{ $displayUnit ? ' / ' . $displayUnit : '' }}</small>
                        </div>
                    </div>
                    <a href="{{ route('product.show', $product->slug) }}" class="mfa-product-add">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>
                        <span>Ajánlatkéréshez adom</span>
                    </a>
                    <a href="{{ route('product.show', $product->slug) }}" class="mfa-product-details">Részletek →</a>
                </div>
            </article>
        @endforeach
    </div>

    <a href="{{ route('home') }}#termekek" class="mfa-view-all">
        <span>Összes termék megtekintése</span> →
    </a>
</section>
@endif
