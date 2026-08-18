@php
    $cartCount = app(\App\Services\CartService::class)->getItemCount();
@endphp
<nav class="bottom-nav" aria-label="Mobil navigáció">

    <a href="{{ route('home') }}" class="bottom-nav-item {{ request()->routeIs('home', 'welcome') ? 'bottom-nav-item--active' : '' }}">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M3 12L12 3l9 9M5 10v9a1 1 0 001 1h4v-5h4v5h4a1 1 0 001-1v-9"/>
        </svg>
        <span>Kezdőlap</span>
    </a>

    <a href="{{ route('home') }}#termekek" class="bottom-nav-item {{ request()->routeIs('category.show') ? 'bottom-nav-item--active' : '' }}">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M4 6h16M4 10h16M4 14h10"/>
        </svg>
        <span>Termékek</span>
    </a>

    <a href="{{ route('cart.index') }}" class="bottom-nav-item {{ request()->routeIs('cart.index') ? 'bottom-nav-item--active' : '' }}">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/>
        </svg>
        <span>Ajánlatkérés</span>
        @if($cartCount > 0)
            <span class="bottom-nav-item-badge">{{ $cartCount }}</span>
        @endif
    </a>

    <a href="#kapcsolat" class="bottom-nav-item">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92Z"/>
        </svg>
        <span>Kapcsolat</span>
    </a>

</nav>
