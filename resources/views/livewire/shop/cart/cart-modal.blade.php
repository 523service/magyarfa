<div>
    @if($show)
        {{-- Backdrop --}}
        <div
            class="fixed inset-0 bg-black/50 z-40 transition-opacity"
            wire:click="closeModal"
            x-data
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
        ></div>

        {{-- Modal --}}
        <div
            class="fixed inset-0 z-50 flex items-center justify-center p-4"
            x-data
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
        >
            <div class="bg-white rounded-lg shadow-xl max-w-lg w-full max-h-[90vh] overflow-y-auto">
                {{-- Header --}}
                <div class="flex items-center justify-between p-4 border-b border-gray-200">
                    <div class="flex items-center gap-2 text-green-600">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.857-9.809a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z" clip-rule="evenodd" />
                        </svg>
                        <span class="font-semibold">Termék hozzáadva a kosárhoz!</span>
                    </div>
                    <button
                        type="button"
                        wire:click="closeModal"
                        class="text-gray-400 hover:text-gray-600 transition-colors"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5">
                            <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z" />
                        </svg>
                    </button>
                </div>

                {{-- Added Product --}}
                <div class="p-4 border-b border-gray-100">
                    <div class="flex gap-4">
                        @if($productImage)
                            <img
                                src="{{ $productImage }}"
                                alt="{{ $productName }}"
                                class="w-20 h-20 object-cover rounded-lg"
                            >
                        @else
                            <div class="w-20 h-20 bg-gray-100 rounded-lg flex items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8 text-gray-400">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                                </svg>
                            </div>
                        @endif
                        <div class="flex-1">
                            <h3 class="font-semibold text-gray-900">{{ $productName }}</h3>
                            <p class="text-sm text-gray-600 mt-1">
                                {{ $quantity }} {{ $unitName }} x {{ number_format($price, 0, ',', ' ') }} Ft
                            </p>
                            <p class="font-semibold text-gray-900 mt-1">
                                {{ number_format($quantity * $price, 0, ',', ' ') }} Ft
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Cart Summary --}}
                <div class="p-4 bg-gray-50">
                    <div class="flex justify-between items-center mb-4">
                        <span class="text-gray-600">Kosár összesen ({{ $cartItemCount }} termék):</span>
                        <span class="font-bold text-lg">{{ number_format($cartTotal, 0, ',', ' ') }} Ft</span>
                    </div>

                    <div class="flex gap-3">
                        <button
                            type="button"
                            wire:click="closeModal"
                            class="flex-1 px-4 py-2.5 border border-gray-300 rounded-lg text-gray-700 font-medium hover:bg-gray-100 transition-colors"
                        >
                            Vásárlás folytatása
                        </button>
                        <button
                            type="button"
                            wire:click="goToCart"
                            class="flex-1 px-4 py-2.5 bg-[var(--accent)] text-white rounded-lg font-medium hover:bg-[var(--accent-hover)] transition-colors"
                        >
                            Kosár megtekintése
                        </button>
                    </div>
                </div>

                {{-- Upsell Products --}}
                @if($upsellProducts->count() > 0)
                    <div class="p-4 border-t border-gray-200">
                        <h4 class="font-semibold text-gray-900 mb-3">Ajánlott termékek</h4>
                        <div class="grid grid-cols-3 gap-3">
                            @foreach($upsellProducts as $upsellProduct)
                                <a
                                    href="{{ route('product.show', $upsellProduct->slug) }}"
                                    class="block group"
                                    wire:navigate
                                >
                                    <div class="aspect-square bg-gray-100 rounded-lg overflow-hidden mb-2">
                                        @if($upsellProduct->getMainImageUrl('thumb'))
                                            <img
                                                src="{{ $upsellProduct->getMainImageUrl('thumb') }}"
                                                alt="{{ $upsellProduct->name }}"
                                                class="w-full h-full object-cover group-hover:scale-105 transition-transform"
                                            >
                                        @else
                                            <div class="w-full h-full flex items-center justify-center">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8 text-gray-400">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                                                </svg>
                                            </div>
                                        @endif
                                    </div>
                                    <h5 class="text-xs font-medium text-gray-900 line-clamp-2 group-hover:text-[var(--accent)]">
                                        {{ $upsellProduct->name }}
                                    </h5>
                                    <p class="text-xs font-semibold text-gray-900 mt-1">
                                        {{ number_format($upsellProduct->price, 0, ',', ' ') }} Ft
                                    </p>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>
