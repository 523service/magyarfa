@php
    $productTotal = isset($products) && method_exists($products, 'total') ? $products->total() : count($products ?? []);
@endphp
<div x-data="{ view: 'list' }">
    <div class="mfa-results-bar">
        <div class="mfa-results-count">{{ $productTotal }} termék található</div>
        <div class="mfa-view-toggle">
            <button type="button" :class="{ 'is-active': view === 'grid' }" @click="view = 'grid'" aria-label="Rács nézet">
                <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="2.5" y="2.5" width="6" height="6" rx="1"/><rect x="11.5" y="2.5" width="6" height="6" rx="1"/><rect x="2.5" y="11.5" width="6" height="6" rx="1"/><rect x="11.5" y="11.5" width="6" height="6" rx="1"/></svg>
            </button>
            <button type="button" :class="{ 'is-active': view === 'list' }" @click="view = 'list'" aria-label="Lista nézet">
                <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><path d="M3 5h14M3 10h14M3 15h14"/></svg>
            </button>
        </div>
    </div>

    {{-- Grid view --}}
    <section class="product-grid" style="margin-top:16px" x-show="view === 'grid'">
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

    {{-- List view --}}
    <div class="mfa-product-list" style="margin-top:16px" x-show="view === 'list'">
        @forelse($products ?? [] as $product)
            @php
                $mainMedia = $product->getMainMedia();
                $unitConfig = $product->unitConfig()->with(['baseUnit'])->first();
                $displayUnit = $unitConfig?->baseUnit?->label_short ?? $product->display_unit ?? null;
                $inStock = ($product->qty ?? 0) > 0 || $product->backorder;
                $rowAttrs = $product->attributeValues->take(2);
            @endphp
            <article class="mfa-product-row">
                <a href="{{ route('product.show', $product->slug) }}" class="mfa-product-row-image">
                    @if($mainMedia)
                        <img src="{{ $mainMedia->getUrl('thumb') }}" alt="{{ $product->name }}" loading="lazy">
                    @else
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="7" width="18" height="13" rx="1"/><path d="M3 11h18M8 7V4h8v3"/></svg>
                    @endif
                </a>
                <div class="mfa-product-row-body">
                    <div class="mfa-product-row-title">
                        <a href="{{ route('product.show', $product->slug) }}">{{ $product->name }}</a>
                    </div>
                    <div class="mfa-product-row-meta">
                        {{ $product->categories->first()?->name }}
                        @if($product->description)
                            &bull; {{ Str::limit(strip_tags($product->description), 40) }}
                        @endif
                    </div>
                    @if($rowAttrs->isNotEmpty())
                        <div class="mfa-product-row-attrs">
                            @foreach($rowAttrs as $attrValue)
                                <span><strong>{{ $attrValue->attribute?->name }}:</strong> {{ $attrValue->text_value ?? $attrValue->number_value }}</span>
                            @endforeach
                        </div>
                    @endif
                    <div class="product-stock {{ $inStock ? 'in-stock' : 'out-of-stock' }}">
                        <svg width="13" height="13" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" style="display:inline;vertical-align:-2px"><path d="M4 10l4 4 8-8"/></svg>
                        {{ $inStock ? 'Készleten' : 'Elfogyott' }}
                    </div>
                </div>
                <div class="mfa-product-row-side">
                    <div class="mfa-product-row-price">
                        {{ number_format($product->getResolvedPrice(), 0, ',', ' ') }} Ft
                        @if($displayUnit)
                            <small>/ {{ $displayUnit }}</small>
                        @endif
                    </div>
                    <a href="{{ route('product.show', $product->slug) }}" class="mfa-product-add">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>
                        <span>Ajánlatkéréshez adom</span>
                    </a>
                    <a href="{{ route('product.show', $product->slug) }}" class="mfa-product-details">Részletek →</a>
                </div>
            </article>
        @empty
            <div class="col-span-full text-center py-12 text-gray-500">
                Nincs megjeleníthető termék.
            </div>
        @endforelse
    </div>
</div>
