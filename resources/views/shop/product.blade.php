<x-layouts.shop>
    <x-slot:title>{{ $product->name ?? 'Termék' }} - MagyarSzigetelés.hu</x-slot:title>

    <div class="py-6 lg:py-10">

        <!-- Breadcrumb -->
        <nav class="product-breadcrumb">
            <a href="{{ route('home') }}">Főoldal</a>
            <span class="breadcrumb-separator">›</span>
            @if(isset($product->categories) && $product->categories->count() > 0)
                <a href="{{ route('category.show', $product->categories->first()->slug) }}">{{ $product->categories->first()->name }}</a>
                <span class="breadcrumb-separator">›</span>
            @endif
            <span class="breadcrumb-current">{{ $product->name ?? 'Termék' }}</span>
        </nav>

        @php $mediaItems = $product->getMainMediaCollection(); @endphp

        <!-- Product Main Section -->
        <div class="product-detail">
            <!-- Image Gallery -->
            <div class="product-gallery">
                <div class="product-main-image" id="mainImage">
                    @if($mediaItems->isNotEmpty())
                        @php
                            $firstMedia = $mediaItems->first();
                            $mainBase64svg = $firstMedia->responsive_images['thumb']['base64svg'] ?? null;
                        @endphp
                        <img
                            src="{{ $mainBase64svg ?: $firstMedia->getUrl('thumb') }}"
                            data-src="{{ $firstMedia->getUrl() }}"
                            sizes="(max-width: 1024px) 100vw, 580px"
                            alt="{{ $product->name }}"
                            id="mainProductImage"
                            class="lazy"
                        >
                    @else
                        <div class="product-image-placeholder">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                            </svg>
                            <span>Nincs kép</span>
                        </div>
                    @endif
                </div>

                @if($mediaItems->count() > 1)
                    <div class="product-thumbnails">
                        @foreach($mediaItems as $index => $media)
                            @php
                                $thumbBase64svg = $media->responsive_images['thumb']['base64svg'] ?? null;
                                $thumbSrcset = $media->getSrcset('thumb');
                            @endphp
                            <button
                                type="button"
                                class="product-thumbnail {{ $index === 0 ? 'active' : '' }}"
                                onclick="changeMainImage('{{ $media->getUrl() }}', this)"
                            >
                                <img
                                    src="{{ $thumbBase64svg ?: $media->getUrl('thumb') }}"
                                    data-src="{{ $media->getUrl('thumb') }}"
                                    @if($thumbSrcset) data-srcset="{{ $thumbSrcset }}" @endif
                                    sizes="70px"
                                    alt="{{ $product->name }} - {{ $index + 1 }}"
                                    class="lazy"
                                >
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Product Info -->
            <div class="product-info">
                <!-- Brand -->
                @if(isset($product->brand) && $product->brand)
                    <div class="product-brand-tag">{{ $product->brand->name }}</div>
                @endif

                <!-- Title -->
                <h1 class="product-detail-title">{{ $product->name ?? 'Termék neve' }}</h1>

                <!-- SKU & Stock -->
                <div class="product-meta-row">
                    @if(isset($product->sku) && $product->sku)
                        <span class="product-sku">Cikkszám: {{ $product->sku }}</span>
                    @endif
                    <span class="product-stock {{ ($product->qty ?? 0) > 0 ? 'in-stock' : 'out-of-stock' }}">
                        @if(($product->qty ?? 0) > 0)
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd" />
                            </svg>
                            Raktáron
                        @else
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z" />
                            </svg>
                            Nincs raktáron
                        @endif
                    </span>
                </div>

                <!-- Price -->
                @php
                    $unitConfig = $product->unitConfig()->with(['baseUnit', 'secondaryUnit'])->first();
                    $displayPrice = $product->getResolvedPrice();
                    $displayUnit = $unitConfig?->baseUnit?->label_short ?? $product->display_unit ?? null;
                @endphp
                <div class="product-price-block"
                    x-data="{
                        price: {{ (int) $displayPrice }},
                        pricePerUnit: {{ (int) $displayPrice }},
                        unitLabel: '{{ addslashes($displayUnit ?? '') }}',
                        hasUnitConfig: {{ $unitConfig ? 'true' : 'false' }}
                    }"
                    @unit-price-updated.window="
                        if ($event.detail.totalPrice !== undefined) {
                            price = $event.detail.totalPrice;
                            pricePerUnit = $event.detail.pricePerUnit;
                            unitLabel = $event.detail.baseUnitLabel;
                        }
                    "
                >
                    <div class="product-price-row">
                        <div class="product-current-price" x-text="new Intl.NumberFormat('hu-HU').format(price) + ' Ft'">
                            {{ number_format($displayPrice, 0, ',', ' ') }} Ft
                        </div>
                        <span class="price-vat">bruttó</span>
                    </div>
                    @if($displayUnit)
                        <div class="product-unit-price" x-show="hasUnitConfig" x-cloak>
                            <span x-text="new Intl.NumberFormat('hu-HU').format(pricePerUnit) + ' Ft / ' + unitLabel">
                                {{ number_format($displayPrice, 0, ',', ' ') }} Ft / {{ $displayUnit }}
                            </span>
                        </div>
                        @if(!$unitConfig)
                            <div class="product-unit-price">
                                Egységár: {{ number_format($displayPrice, 0, ',', ' ') }} Ft / {{ $displayUnit }}
                            </div>
                        @endif
                    @endif
                    @if($unitConfig?->min_order_qty && $unitConfig->min_order_qty > 1)
                        <div class="product-min-order">
                            Min. rendelés: {{ rtrim(rtrim(number_format($unitConfig->min_order_qty, 4, ',', ' '), '0'), ',') }} {{ $displayUnit }}
                            @if($unitConfig->secondaryUnit && $unitConfig->secondary_unit_qty)
                                ({{ (int) ceil($unitConfig->min_order_qty / $unitConfig->secondary_unit_qty) }} {{ $unitConfig->secondaryUnit->label_short }})
                            @endif
                        </div>
                    @endif

                    @if($competitorLinks->isNotEmpty())
                        <div class="competitor-price-block">
                            <div class="competitor-price-label d-none">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" width="12" height="12">
                                    <path fill-rule="evenodd" d="M15 8A7 7 0 1 1 1 8a7 7 0 0 1 14 0Zm-6 3.5a1 1 0 1 1-2 0 1 1 0 0 1 2 0Zm-.25-6.75a.75.75 0 0 0-1.5 0v.69a2.25 2.25 0 0 0 .59 4.44h.66a.75.75 0 0 1 0 1.5h-1.5a.75.75 0 0 0 0 1.5h.75v.31a.75.75 0 0 0 1.5 0v-.37a2.25 2.25 0 0 0-.59-4.44h-.66a.75.75 0 0 1 0-1.5h1.5a.75.75 0 0 0 0-1.5h-.75v-.69Z" clip-rule="evenodd" />
                                </svg>
                                Konkurencia árak
                            </div>
                            @foreach($competitorLinks as $cl)
                                <div class="competitor-price-row">
                                    <img src="{{ $cl->competitor_logo }}" alt="{{ $cl->competitor_name }}" class="competitor-logo">
                                    <span class="competitor-price-value">
                                        @if($cl->scraped_sale_price)
                                            <span class="competitor-sale-price">{{ number_format((float) $cl->scraped_sale_price, 0, ',', ' ') }} Ft</span>
                                            <span class="competitor-original-price">{{ number_format((float) $cl->scraped_price, 0, ',', ' ') }} Ft</span>
                                        @else
                                            {{ number_format((float) $cl->scraped_price, 0, ',', ' ') }} Ft
                                        @endif
                                    </span>
                                    <span class="competitor-price-name">{{ $cl->competitor_name }}</span>
                                    <span class="competitor-price-updated">{{ $cl->last_scraped_at?->diffForHumans() }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <!-- Short Description -->
                @if(isset($product->description) && $product->description)
                    <div class="product-short-desc">
                        {!!  Str::limit($product->description, 200) !!}
                    </div>
                @endif

                <!-- Attributes Section -->
                @if(isset($attributes) && count($attributes) > 0)
                    <div class="product-attributes">
                        <h3 class="attributes-title">Jellemzők</h3>
                        <div class="attributes-grid">
                            @foreach($attributes as $attribute)
                                @php $value = $attribute['value'] ?? null; @endphp
                                @if($value !== null && $value !== '' && $value != 0)
                                    <div class="attribute-item">
                                        <span class="attribute-label">{{ $attribute['name'] ?? $attribute['label'] ?? 'Tulajdonság' }}</span>
                                        <span class="attribute-value">{{ $value }}</span>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Variant Selection (if applicable) -->
                @if(isset($variants) && count($variants) > 0)
                    <div class="product-variants">
                        @foreach($variants as $variantGroup)
                            @php
                                $options = collect($variantGroup['options'] ?? [])->filter(function ($option) {
                                    $value = $option['value'] ?? $option;
                                    return $value !== null && $value !== '' && $value != 0;
                                })->values();
                            @endphp
                            @if($options->isNotEmpty())
                                <div class="variant-group">
                                    <label class="variant-label">{{ $variantGroup['name'] ?? 'Válasszon' }}</label>
                                    <div class="variant-options">
                                        @foreach($options as $option)
                                            <button
                                                type="button"
                                                class="variant-option {{ $loop->first ? 'active' : '' }}"
                                                data-variant="{{ $option['id'] ?? $option['value'] ?? '' }}"
                                            >
                                                {{ $option['label'] ?? $option['value'] ?? $option }}
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                @endif

                <!-- Add to Cart -->
                <livewire:shop.cart.add-to-cart-button :product="$product" />

                {{--
                <!-- Trust Badges -->
                <div class="product-trust-badges">
                    <div class="trust-badge">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M1 8.25a1.25 1.25 0 1 1 2.5 0v7.5a1.25 1.25 0 1 1-2.5 0v-7.5ZM11 3V1.7c0-.268.14-.526.395-.607A2 2 0 0 1 14 3c0 .995-.182 1.948-.514 2.826-.204.54.166 1.174.744 1.174h2.52c1.243 0 2.261 1.01 2.146 2.247a23.864 23.864 0 0 1-1.341 5.974 1.999 1.999 0 0 1-1.904 1.379H9.25a3 3 0 0 1-2.121-.879L4.8 13.392A.999.999 0 0 1 5 11.89a1.75 1.75 0 0 0 1.75-1.75V3.5c0-.88.54-1.631 1.303-1.939A18.095 18.095 0 0 1 11 3Z" />
                        </svg>
                        <span>14 nap visszaküldés</span>
                    </div>
                    <div class="trust-badge">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M1 11.27c0-.246.033-.492.099-.73l1.523-5.521A2.75 2.75 0 0 1 5.273 3h9.454a2.75 2.75 0 0 1 2.651 2.019l1.523 5.52c.066.239.099.485.099.732V15a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2v-3.73Zm3.068-5.852A1.25 1.25 0 0 1 5.273 4.5h9.454a1.25 1.25 0 0 1 1.205.918l1.523 5.52c.006.02.01.041.015.062H14a1 1 0 0 0-.86.49l-.606 1.02a1 1 0 0 1-.86.49H8.326a1 1 0 0 1-.86-.49l-.606-1.02A1 1 0 0 0 6 11H2.53l.015-.062 1.523-5.52Z" clip-rule="evenodd" />
                        </svg>
                        <span>Ingyenes szállítás 50.000 Ft felett</span>
                    </div>
                    <div class="trust-badge">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M9.661 2.237a.531.531 0 0 1 .678 0 11.947 11.947 0 0 0 7.078 2.749.5.5 0 0 1 .479.425c.069.52.104 1.05.104 1.59 0 5.162-3.26 9.563-7.834 11.256a.48.48 0 0 1-.332 0C5.26 16.564 2 12.163 2 7c0-.538.035-1.069.104-1.589a.5.5 0 0 1 .48-.425 11.947 11.947 0 0 0 7.077-2.75Zm4.196 5.954a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z" clip-rule="evenodd" />
                        </svg>
                        <span>Biztonságos fizetés</span>
                    </div>
                </div>
                --}}
            </div>
        </div>

        <!-- Product Tabs / Details -->
        <div class="product-tabs">
            <div class="tabs-header">
                <button type="button" class="tab-btn active" data-tab="description">Leírás</button>
                <button type="button" class="tab-btn" data-tab="specifications">Specifikációk</button>
                <button type="button" class="tab-btn" data-tab="shipping">Szállítás</button>
            </div>

            <div class="tabs-content">
                <!-- Description Tab -->
                <div class="tab-pane active" id="tab-description">
                    @if(isset($product->description) && $product->description)
                        <div class="prose">
                            {!!  $product->description !!}
                        </div>
                    @else
                        <p class="text-muted">Nincs részletes leírás ehhez a termékhez.</p>
                    @endif
                </div>

                <!-- Specifications Tab -->
                <div class="tab-pane" id="tab-specifications">
                    @if(isset($specifications) && count($specifications) > 0)
                        <table class="spec-table">
                            <tbody>
                                @foreach($specifications as $spec)
                                    <tr>
                                        <th>{{ $spec['name'] ?? $spec['label'] ?? '' }}</th>
                                        <td>{{ $spec['value'] ?? '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @elseif(isset($attributes) && count($attributes) > 0)
                        <table class="spec-table">
                            <tbody>
                                @foreach($attributes as $attribute)
                                    <tr>
                                        <th>{{ $attribute['name'] ?? $attribute['label'] ?? '' }}</th>
                                        <td>{{ $attribute['value'] ?? '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <p class="text-muted">Nincsenek specifikációk megadva.</p>
                    @endif
                </div>

                <!-- Shipping Tab -->
                <div class="tab-pane" id="tab-shipping">
                    <div class="shipping-info">
                        <div class="shipping-method">
                            <h4>Házhozszállítás</h4>
                            <p>Szállítási idő: érdeklődjön</p>
                            <p>Szállítási költség: ({{ number_format(config('shop.free_shipping_threshold'), 0, ',', ' ') }} Ft felett ingyenes)</p>
                        </div>
                        <div class="shipping-method">
                            <h4>Személyes átvétel</h4>
                            <p>Telephelyünkön díjmentesen átvehető</p>
                            <p></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Related Products -->
        @if(isset($relatedProducts) && $relatedProducts->count() > 0)
            <section class="related-products">
                <h2 class="section-title">Kapcsolódó termékek</h2>
                <div class="product-grid">
                    @foreach($relatedProducts->take(4) as $relatedProduct)
                        <article class="product-card">
                            <a href="{{ route('product.show', $relatedProduct->slug) }}" class="product-image">
                                @php $relatedMedia = $relatedProduct->getMainMedia(); @endphp
                                @if($relatedMedia)
                                    @php
                                        $relatedBase64svg = $relatedMedia->responsive_images['thumb']['base64svg'] ?? null;
                                        $relatedSrcset = $relatedMedia->getSrcset('thumb');
                                    @endphp
                                    <img
                                        src="{{ $relatedBase64svg ?: $relatedMedia->getUrl('thumb') }}"
                                        data-src="{{ $relatedMedia->getUrl('thumb') }}"
                                        @if($relatedSrcset) data-srcset="{{ $relatedSrcset }}" @endif
                                        sizes="(max-width: 520px) calc(100vw - 2rem), (max-width: 768px) calc(50vw - 2rem), 280px"
                                        alt="{{ $relatedProduct->name }}"
                                        class="lazy"
                                    >
                                @else
                                    <span class="text-gray-400 text-sm">Termékkép</span>
                                @endif
                            </a>
                            <div class="product-title">
                                <a href="{{ route('product.show', $relatedProduct->slug) }}">{{ $relatedProduct->name }}</a>
                            </div>
                            <div class="product-footer">
                                <div class="product-price">
                                    {{ number_format($relatedProduct->getResolvedPrice(), 0, ',', ' ') }} Ft
                                    <small>bruttó / {{ $relatedProduct->unit ?? 'm²' }}</small>
                                </div>
                                <a href="{{ route('product.show', $relatedProduct->slug) }}" class="btn-outline">Részletek</a>
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>
        @endif

    </div>

    @push('scripts')
    <script>
        // Image Gallery
        function changeMainImage(src, thumbnail) {
            const mainImg = document.getElementById('mainProductImage');
            mainImg.src = src;
            mainImg.classList.remove('lazy');
            document.querySelectorAll('.product-thumbnail').forEach(t => t.classList.remove('active'));
            thumbnail.classList.add('active');
        }

        // Tabs
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const tabId = this.dataset.tab;

                // Update buttons
                document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');

                // Update panes
                document.querySelectorAll('.tab-pane').forEach(pane => pane.classList.remove('active'));
                document.getElementById('tab-' + tabId).classList.add('active');
            });
        });

        // Variant Selection
        document.querySelectorAll('.variant-option').forEach(option => {
            option.addEventListener('click', function() {
                const group = this.closest('.variant-group');
                group.querySelectorAll('.variant-option').forEach(o => o.classList.remove('active'));
                this.classList.add('active');
            });
        });
    </script>
    @endpush
</x-layouts.shop>
