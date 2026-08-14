<nav class="bottom-nav" aria-label="Mobil navigáció">

    <a href="{{ route('home') }}" class="bottom-nav-item {{ request()->routeIs('home', 'welcome') ? 'bottom-nav-item--active' : '' }}">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M3 12L12 3l9 9M5 10v9a1 1 0 001 1h4v-5h4v5h4a1 1 0 001-1v-9"/>
        </svg>
        <span>Főoldal</span>
    </a>

    <a href="{{ route('search.index') }}" class="bottom-nav-item {{ request()->routeIs('search.*') && !request()->routeIs('search.defaults', 'search.autocomplete') ? 'bottom-nav-item--active' : '' }}">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M4 6h16M4 10h16M4 14h10"/>
        </svg>
        <span>Kategóriák</span>
    </a>

    <button
        type="button"
        class="bottom-nav-item bottom-nav-item--search"
        onclick="window.dispatchEvent(new CustomEvent('open-search'))"
        aria-label="Keresés megnyitása"
    >
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
        </svg>
        <span>Keresés</span>
    </button>

    <a href="{{ route('cart.index') }}" class="bottom-nav-item {{ request()->routeIs('cart.index') ? 'bottom-nav-item--active' : '' }}">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><path d="M3 6h18M16 10a4 4 0 01-8 0"/>
        </svg>
        <span>Kosár</span>
    </a>

    @auth
        <a href="{{ route('profile.edit') }}" class="bottom-nav-item {{ request()->routeIs('profile.*') ? 'bottom-nav-item--active' : '' }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/>
            </svg>
            <span>Fiókom</span>
        </a>
    @else
        <a href="{{ route('login') }}" class="bottom-nav-item {{ request()->routeIs('login', 'register') ? 'bottom-nav-item--active' : '' }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/>
            </svg>
            <span>Belépés</span>
        </a>
    @endauth

</nav>