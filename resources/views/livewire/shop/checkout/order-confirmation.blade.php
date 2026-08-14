<div class="py-10 lg:py-16">
    <div class="max-w-2xl mx-auto">
        {{-- Success icon --}}
        <div class="flex justify-center mb-6">
            <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-10 h-10 text-green-600">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 0 1 0 1.414l-8 8a1 1 0 0 1-1.414 0l-4-4a1 1 0 0 1 1.414-1.414L8 12.586l7.293-7.293a1 1 0 0 1 1.414 0Z" clip-rule="evenodd" />
                </svg>
            </div>
        </div>

        {{-- Heading --}}
        <h1 class="text-3xl font-bold text-gray-900 text-center mb-2">Rendelés sikeres!</h1>
        <p class="text-gray-500 text-center mb-6">Köszönöm a bizalmát, hamarosan gondoskodunk a teljesítésről.</p>

        <x-shop.price-notice class="mb-6" />

        {{-- Order number --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6 mb-4">
            <div class="flex items-center justify-between mb-4">
                <span class="text-sm text-gray-500">Rendelésszám</span>
                <span class="font-bold text-lg text-[var(--accent)]">{{ $order->number }}</span>
            </div>

            <div class="flex items-center justify-between mb-4">
                <span class="text-sm text-gray-500">Fizetési mód</span>
                <span class="text-sm font-medium text-gray-800">
                    @if ($order->payments->isNotEmpty())
                        {{ \App\Enums\PaymentMethod::from($order->payments->first()->method)->getLabel() }}
                    @endif
                </span>
            </div>

            <div class="flex items-center justify-between mb-4">
                <span class="text-sm text-gray-500">Szállítási mód</span>
                <span class="text-sm font-medium text-gray-800">
                    {{ \App\Enums\ShippingMethod::from($order->shipping_method)->getLabel() }}
                </span>
            </div>

            <div class="flex items-center justify-between">
                <span class="text-sm text-gray-500">Dátum</span>
                <span class="text-sm text-gray-800">{{ $order->created_at->format('Y. m. d.') }}</span>
            </div>
        </div>

        {{-- Items --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6 mb-4">
            <h3 class="text-sm font-semibold text-gray-900 mb-3">Rendelt termékek</h3>

            <div class="space-y-2">
                @foreach ($order->items as $item)
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-700">
                            @if ($item->shop_product_id)
                                {{ \App\Models\Shop\Product::find($item->shop_product_id)?->name ?? 'Termék' }}
                            @else
                                Termék
                            @endif
                            <span class="text-gray-400">
                                @if ($item->secondary_qty && $item->secondary_unit)
                                    × {{ number_format($item->secondary_qty, 0, ',', ' ') }} {{ $item->secondary_unit }}
                                    <span class="text-xs">(= {{ $item->qty }} {{ $item->unit_name }})</span>
                                @else
                                    × {{ $item->qty }}{{ $item->unit_name ? ' ' . $item->unit_name : '' }}
                                @endif
                            </span>
                        </span>
                        <span class="font-medium text-gray-900">{{ number_format($item->unit_price * $item->qty, 0, ',', ' ') }} Ft</span>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Totals --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6 mb-6">
            <div class="flex justify-between text-sm text-gray-600 mb-2">
                <span>Részösszeg</span>
                <span>{{ number_format($order->total_price - $order->shipping_price, 0, ',', ' ') }} Ft</span>
            </div>
            <div class="flex justify-between text-sm text-gray-600 mb-2">
                <span>Szállítás</span>
                <span>
                    @if ((int) $order->shipping_price === 0)
                        <span class="text-green-600">Ingyenes</span>
                    @else
                        {{ number_format($order->shipping_price, 0, ',', ' ') }} Ft
                    @endif
                </span>
            </div>
            <div class="border-t border-gray-200 pt-3 mt-3 flex justify-between font-semibold text-lg text-gray-900">
                <span>Végösszeg</span>
                <span>{{ number_format($order->total_price, 0, ',', ' ') }} Ft</span>
            </div>
        </div>

        {{-- Shipping address --}}
        @php $shippingAddr = $order->addresses->firstWhere('type', 'shipping') @endphp
        @if ($shippingAddr)
            <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6 mb-6">
                <h3 class="text-sm font-semibold text-gray-900 mb-2">Szállítási cím</h3>
                <p class="text-sm text-gray-700">{{ $shippingAddr->name }}</p>
                <p class="text-sm text-gray-600">{{ $shippingAddr->street }}</p>
                <p class="text-sm text-gray-600">{{ $shippingAddr->zip }} {{ $shippingAddr->city }}</p>
            </div>
        @endif

        {{-- Back button --}}
        <div class="text-center">
            <a href="{{ route('home') }}"
                class="inline-flex items-center gap-2 px-6 py-3 bg-[var(--accent)] text-white rounded-lg font-medium hover:bg-[var(--accent-hover)] transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5">
                    <path fill-rule="evenodd" d="M17 10a.75.75 0 0 1-.75.75H5.612l4.158 3.96a.75.75 0 1 1-1.04 1.08l-5.5-5.25a.75.75 0 0 1 0-1.08l5.5-5.25a.75.75 0 1 1 1.04 1.08L5.612 9.25H16.25A.75.75 0 0 1 17 10Z" clip-rule="evenodd" />
                </svg>
                Vissza a főoldalra
            </a>
        </div>
    </div>
</div>
