<div class="py-6 lg:py-10">
    {{-- Breadcrumb --}}
    <nav class="product-breadcrumb mb-6">
        <a href="{{ route('home') }}">Főoldal</a>
        <span class="breadcrumb-separator">></span>
        <span class="breadcrumb-current">Kosár</span>
    </nav>

    <h1 class="text-2xl lg:text-3xl font-bold text-gray-900 mb-6">Kosár</h1>

    @if($items->isNotEmpty())
        <x-shop.price-notice class="mb-6" />
    @endif

    @if($items->isEmpty())
        {{-- Empty Cart --}}
        <div class="bg-white rounded-lg shadow-sm p-8 text-center">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-16 h-16 mx-auto text-gray-300 mb-4">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" />
            </svg>
            <h2 class="text-xl font-semibold text-gray-900 mb-2">A kosarad üres</h2>
            <p class="text-gray-600 mb-6">Nézz körül a kínálatunkban és találd meg a számodra megfelelő termékeket!</p>
            <a href="{{ route('home') }}" class="inline-flex items-center gap-2 px-6 py-3 bg-[var(--accent)] text-white rounded-lg font-medium hover:bg-[var(--accent-hover)] transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5">
                    <path fill-rule="evenodd" d="M17 10a.75.75 0 0 1-.75.75H5.612l4.158 3.96a.75.75 0 1 1-1.04 1.08l-5.5-5.25a.75.75 0 0 1 0-1.08l5.5-5.25a.75.75 0 1 1 1.04 1.08L5.612 9.25H16.25A.75.75 0 0 1 17 10Z" clip-rule="evenodd" />
                </svg>
                Vásárlás folytatása
            </a>
        </div>
    @else
        <div class="lg:grid lg:grid-cols-3 lg:gap-8">
            {{-- Cart Items --}}
            <div class="lg:col-span-2">
                <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                    {{-- Header --}}
                    <div class="hidden lg:grid lg:grid-cols-12 gap-4 p-4 bg-gray-50 border-b border-gray-200 text-sm font-medium text-gray-600">
                        <div class="col-span-6">Termék</div>
                        <div class="col-span-2 text-center">Egységár</div>
                        <div class="col-span-2 text-center">Mennyiség</div>
                        <div class="col-span-2 text-right">Összesen</div>
                    </div>

                    {{-- Items --}}
                    @foreach($items as $item)
                        <div class="p-4 border-b border-gray-100 last:border-b-0" wire:key="cart-item-{{ $item->id }}">
                            <div class="lg:grid lg:grid-cols-12 lg:gap-4 lg:items-center">
                                {{-- Product Info --}}
                                <div class="col-span-6 flex gap-4 mb-4 lg:mb-0">
                                    @if($item->attributes->image_url)
                                        <img
                                            src="{{ $item->attributes->image_url }}"
                                            alt="{{ $item->name }}"
                                            class="w-20 h-20 object-cover rounded-lg flex-shrink-0"
                                        >
                                    @else
                                        <div class="w-20 h-20 bg-gray-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8 text-gray-400">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                                            </svg>
                                        </div>
                                    @endif
                                    <div class="flex-1 min-w-0">
                                        <a
                                            href="{{ route('product.show', $item->attributes->slug ?? '#') }}"
                                            class="font-medium text-gray-900 hover:text-[var(--accent)] line-clamp-2"
                                        >
                                            {{ $item->name }}
                                        </a>
                                        @if($item->attributes->secondary_unit && $item->attributes->secondary_qty)
                                            <p class="text-sm text-gray-500 mt-1">
                                                {{ number_format($item->attributes->secondary_qty, 0, ',', ' ') }} {{ $item->attributes->secondary_unit }}
                                                <span class="text-gray-400">(= {{ $item->quantity }} {{ $item->attributes->unit_name }})</span>
                                            </p>
                                        @elseif($item->attributes->unit_name)
                                            <p class="text-sm text-gray-500 mt-1">
                                                Egység: {{ $item->attributes->unit_name }}
                                            </p>
                                        @endif
                                        <button
                                            type="button"
                                            wire:click="removeItem('{{ $item->id }}')"
                                            wire:loading.attr="disabled"
                                            class="text-sm text-red-600 hover:text-red-700 mt-2 lg:hidden"
                                        >
                                            Törlés
                                        </button>
                                    </div>
                                </div>

                                {{-- Unit Price --}}
                                <div class="col-span-2 text-center hidden lg:block">
                                    <span class="font-medium">{{ number_format($item->price, 0, ',', ' ') }} Ft</span>
                                </div>

                                {{-- Quantity --}}
                                <div class="col-span-2 flex items-center justify-between lg:justify-center mb-4 lg:mb-0">
                                    <span class="text-sm text-gray-600 lg:hidden">Mennyiség:</span>
                                    <div class="flex items-center border border-gray-300 rounded-lg">
                                        <button
                                            type="button"
                                            wire:click="decrementQuantity('{{ $item->id }}')"
                                            wire:loading.attr="disabled"
                                            class="px-3 py-1.5 text-gray-600 hover:bg-gray-100 transition-colors disabled:opacity-50"
                                            @if($item->quantity <= 1) disabled @endif
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4">
                                                <path fill-rule="evenodd" d="M4 10a.75.75 0 0 1 .75-.75h10.5a.75.75 0 0 1 0 1.5H4.75A.75.75 0 0 1 4 10Z" clip-rule="evenodd" />
                                            </svg>
                                        </button>
                                        <span class="px-3 py-1.5 min-w-[3rem] text-center font-medium">{{ $item->quantity }}</span>
                                        <button
                                            type="button"
                                            wire:click="incrementQuantity('{{ $item->id }}')"
                                            wire:loading.attr="disabled"
                                            class="px-3 py-1.5 text-gray-600 hover:bg-gray-100 transition-colors disabled:opacity-50"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4">
                                                <path d="M10.75 4.75a.75.75 0 0 0-1.5 0v4.5h-4.5a.75.75 0 0 0 0 1.5h4.5v4.5a.75.75 0 0 0 1.5 0v-4.5h4.5a.75.75 0 0 0 0-1.5h-4.5v-4.5Z" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>

                                {{-- Row Total --}}
                                <div class="col-span-2 flex items-center justify-between lg:justify-end">
                                    <span class="text-sm text-gray-600 lg:hidden">Összesen:</span>
                                    <div class="flex items-center gap-4">
                                        <span class="font-semibold text-gray-900">
                                            {{ number_format($item->price * $item->quantity, 0, ',', ' ') }} Ft
                                        </span>
                                        <button
                                            type="button"
                                            wire:click="removeItem('{{ $item->id }}')"
                                            wire:loading.attr="disabled"
                                            class="text-gray-400 hover:text-red-600 transition-colors hidden lg:block"
                                            title="Torles"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5">
                                                <path fill-rule="evenodd" d="M8.75 1A2.75 2.75 0 0 0 6 3.75v.443c-.795.077-1.584.176-2.365.298a.75.75 0 1 0 .23 1.482l.149-.022.841 10.518A2.75 2.75 0 0 0 7.596 19h4.807a2.75 2.75 0 0 0 2.742-2.53l.841-10.519.149.023a.75.75 0 0 0 .23-1.482A41.03 41.03 0 0 0 14 4.193V3.75A2.75 2.75 0 0 0 11.25 1h-2.5ZM10 4c.84 0 1.673.025 2.5.075V3.75c0-.69-.56-1.25-1.25-1.25h-2.5c-.69 0-1.25.56-1.25 1.25v.325C8.327 4.025 9.16 4 10 4ZM8.58 7.72a.75.75 0 0 0-1.5.06l.3 7.5a.75.75 0 1 0 1.5-.06l-.3-7.5Zm4.34.06a.75.75 0 1 0-1.5-.06l-.3 7.5a.75.75 0 1 0 1.5.06l.3-7.5Z" clip-rule="evenodd" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Cart Actions --}}
                <div class="flex flex-wrap gap-4 mt-4">
                    <a href="{{ route('home') }}" class="inline-flex items-center gap-2 px-4 py-2 border border-gray-300 rounded-lg text-gray-700 font-medium hover:bg-gray-100 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5">
                            <path fill-rule="evenodd" d="M17 10a.75.75 0 0 1-.75.75H5.612l4.158 3.96a.75.75 0 1 1-1.04 1.08l-5.5-5.25a.75.75 0 0 1 0-1.08l5.5-5.25a.75.75 0 1 1 1.04 1.08L5.612 9.25H16.25A.75.75 0 0 1 17 10Z" clip-rule="evenodd" />
                        </svg>
                        Vásárlás folytatása
                    </a>
                    <button
                        type="button"
                        wire:click="clearCart"
                        wire:loading.attr="disabled"
                        wire:confirm="Biztosan torold az osszes terméket a kosarbol?"
                        class="inline-flex items-center gap-2 px-4 py-2 border border-red-300 rounded-lg text-red-600 font-medium hover:bg-red-50 transition-colors"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5">
                            <path fill-rule="evenodd" d="M8.75 1A2.75 2.75 0 0 0 6 3.75v.443c-.795.077-1.584.176-2.365.298a.75.75 0 1 0 .23 1.482l.149-.022.841 10.518A2.75 2.75 0 0 0 7.596 19h4.807a2.75 2.75 0 0 0 2.742-2.53l.841-10.519.149.023a.75.75 0 0 0 .23-1.482A41.03 41.03 0 0 0 14 4.193V3.75A2.75 2.75 0 0 0 11.25 1h-2.5ZM10 4c.84 0 1.673.025 2.5.075V3.75c0-.69-.56-1.25-1.25-1.25h-2.5c-.69 0-1.25.56-1.25 1.25v.325C8.327 4.025 9.16 4 10 4ZM8.58 7.72a.75.75 0 0 0-1.5.06l.3 7.5a.75.75 0 1 0 1.5-.06l-.3-7.5Zm4.34.06a.75.75 0 1 0-1.5-.06l-.3 7.5a.75.75 0 1 0 1.5.06l.3-7.5Z" clip-rule="evenodd" />
                        </svg>
                        Kosár ürítése
                    </button>
                </div>
            </div>

            {{-- Order Summary --}}
            <div class="lg:col-span-1 mt-6 lg:mt-0">
                <div class="bg-white rounded-lg shadow-sm p-6 sticky top-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Összegzés</h2>

                    <div class="space-y-3 mb-6">
                        <div class="flex justify-between text-gray-600">
                            <span>Részösszeg ({{ $itemCount }} termék)</span>
                            <span>{{ number_format($subTotal, 0, ',', ' ') }} Ft</span>
                        </div>
                        <div class="flex justify-between text-gray-600">
                            <span>Szállítás</span>
                            <span class="text-sm">Számítva a pénztárnál</span>
                        </div>
                        <div class="border-t border-gray-200 pt-3">
                            <div class="flex justify-between font-semibold text-lg text-gray-900">
                                <span>Végösszeg</span>
                                <span>{{ number_format($total, 0, ',', ' ') }} Ft</span>
                            </div>
                            <p class="text-xs text-gray-500 mt-1">Az ár tartalmazza az ÁFA-t</p>
                        </div>
                    </div>

                    <a
                        href="{{ route('checkout.index') }}"
                        class="block w-full px-6 py-3 bg-[var(--accent)] text-white rounded-lg font-semibold hover:bg-[var(--accent-hover)] transition-colors text-center"
                    >
                        Tovább a pénztárhoz
                    </a>

                    {{-- Trust badges --}}
                    <div class="mt-6 pt-6 border-t border-gray-200 space-y-3">
                        <div class="flex items-center gap-2 text-sm text-gray-600">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5 text-green-600">
                                <path fill-rule="evenodd" d="M9.661 2.237a.531.531 0 0 1 .678 0 11.947 11.947 0 0 0 7.078 2.749.5.5 0 0 1 .479.425c.069.52.104 1.05.104 1.59 0 5.162-3.26 9.563-7.834 11.256a.48.48 0 0 1-.332 0C5.26 16.564 2 12.163 2 7c0-.538.035-1.069.104-1.589a.5.5 0 0 1 .48-.425 11.947 11.947 0 0 0 7.077-2.75Z" clip-rule="evenodd" />
                            </svg>
                            <span>Biztonságos fizetés</span>
                        </div>
                        <div class="flex items-center gap-2 text-sm text-gray-600">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5 text-green-600">
                                <path d="M6.5 3c-1.051 0-2.093.04-3.125.117A1.49 1.49 0 0 0 2 4.607V10.5h9V4.606c0-.771-.59-1.43-1.375-1.489A41.568 41.568 0 0 0 6.5 3ZM2 12v2.5A1.5 1.5 0 0 0 3.5 16h.041a3 3 0 0 1 5.918 0h.791a.75.75 0 0 0 .75-.75V12H2Z" />
                                <path d="M6.5 18a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3ZM13.5 18a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3ZM13.5 3c-1.051 0-2.093.04-3.125.117A1.49 1.49 0 0 0 9 4.607V10.5h9V4.606c0-.771-.59-1.43-1.375-1.489A41.568 41.568 0 0 0 13.5 3ZM18 12h-9v3.25c0 .414.336.75.75.75h.791a3 3 0 0 1 5.918 0h.041a1.5 1.5 0 0 0 1.5-1.5V12Z" />
                            </svg>
                            <span>Gyors szállítás 1-3 munkanap</span>
                        </div>
                        <div class="flex items-center gap-2 text-sm text-gray-600">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5 text-green-600">
                                <path fill-rule="evenodd" d="M15.312 11.424a5.5 5.5 0 0 1-9.201 2.466l-.312-.311h2.433a.75.75 0 0 0 0-1.5H3.989a.75.75 0 0 0-.75.75v4.242a.75.75 0 0 0 1.5 0v-2.43l.31.31a7 7 0 0 0 11.712-3.138.75.75 0 0 0-1.449-.39Zm1.23-3.723a.75.75 0 0 0 .219-.53V2.929a.75.75 0 0 0-1.5 0v2.43l-.31-.31A7 7 0 0 0 3.239 8.188a.75.75 0 1 0 1.448.389 5.5 5.5 0 0 1 9.2-2.466l.311.311H11.77a.75.75 0 0 0 0 1.5h4.243a.75.75 0 0 0 .53-.22Z" clip-rule="evenodd" />
                            </svg>
                            <span>14 napos visszaküldés</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
