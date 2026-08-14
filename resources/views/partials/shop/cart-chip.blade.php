@php
    $cartCount = $cartCount ?? session('cart.count', 0);
    $cartTotal = $cartTotal ?? session('cart.total', 0);
@endphp

<a href="{{ route('home') }}" class="cart-chip">
    <div class="cart-chip-icon">🛒</div>
    <div>
        <div style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.14em;">
            Kosár
        </div>
        <div style="font-size: 13px; font-weight: 500;">
            {{ $cartCount }} termék – {{ number_format($cartTotal, 0, ',', ' ') }} Ft
        </div>
    </div>
</a>
