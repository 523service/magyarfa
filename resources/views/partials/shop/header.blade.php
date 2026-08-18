<header class="header">
    <div class="container header-inner">

        @php
            use Carbon\Carbon;

            $tz    = 'Europe/Budapest';
            $now   = Carbon::now($tz);
            $today = $now->toDateString();
            $time  = $now->format('H:i');
            $dow   = $now->dayOfWeekIso;
            $hours = config('shop.opening_hours');
            $special = $hours['special_days'] ?? [];

            $upcomingSpecial = collect($special)
                ->filter(fn ($v, $date) => $date >= $today)
                ->sortKeys()
                ->take(3);

            if (array_key_exists($today, $special)) {
                $todaySpecial = $special[$today];
                if ($todaySpecial['hours'] === null) {
                    $isOpen = false;
                } else {
                    [$openT, $closeT] = explode('–', $todaySpecial['hours']);
                    $isOpen = $time >= trim($openT) && $time < trim($closeT);
                }
            } elseif ($dow <= 5) {
                $isOpen = $time >= $hours['weekday']['open'] && $time < $hours['weekday']['close'];
            } elseif ($dow === 6) {
                $isOpen = $time >= $hours['saturday']['open'] && $time < $hours['saturday']['close'];
            } else {
                $isOpen = false;
            }
        @endphp

        {{-- Hamburger (mobile only) --}}
        <div class="header-hamburger-wrap" x-data="{ open: false }">
            <button
                type="button"
                class="header-hamburger"
                @click="open = !open"
                @click.outside="open = false"
                aria-label="Menü megnyitása"
            >
                <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>
            <div class="mobile-menu-dropdown" x-show="open" x-transition @click="open = false">
                <a href="{{ route('home') }}" class="mobile-menu-item">Kezdőlap</a>
                <a href="{{ route('home') }}#termekek" class="mobile-menu-item">Termékek</a>
                <a href="#" class="mobile-menu-item">Rólunk</a>
                <a href="#" class="mobile-menu-item">Szolgáltatások</a>
                <a href="#kapcsolat" class="mobile-menu-item">Kapcsolat</a>
                <div class="mobile-menu-divider"></div>
                <a href="tel:+36709411982" class="mobile-menu-item">
                    <span class="mobile-menu-icon">☎</span>
                    <span>+36-70-941-1982</span>
                </a>
                <div class="mobile-menu-item mobile-menu-item--plain">
                    <span class="mobile-menu-icon">
                        <span class="hours-dot {{ $isOpen ? 'hours-dot--open' : 'hours-dot--closed' }}"></span>
                    </span>
                    <span>Nyitvatartás: {{ $isOpen ? 'Nyitva' : 'Zárva' }}</span>
                </div>
                <div class="mobile-menu-divider"></div>
                @auth
                    <a href="{{ route('profile.edit') }}" class="mobile-menu-item">
                        <span class="mobile-menu-icon">👤</span>
                        <span>Fiókom</span>
                    </a>
                @else
                    <a href="{{ route('login') }}" class="mobile-menu-item">
                        <span class="mobile-menu-icon">🔑</span>
                        <span>Belépés</span>
                    </a>
                    <a href="{{ route('register') }}" class="mobile-menu-item">
                        <span class="mobile-menu-icon">✏️</span>
                        <span>Regisztráció</span>
                    </a>
                @endauth
            </div>
        </div>

        @include('partials.shop.logo')

        <nav class="main-nav">
            <a href="{{ route('home') }}" class="{{ request()->routeIs('home', 'welcome', 'shop.home') ? 'is-active' : '' }}">Kezdőlap</a>
            <a href="{{ route('home') }}#termekek" class="{{ request()->routeIs('category.show') ? 'is-active' : '' }}">Termékek</a>
            <a href="#">Rólunk</a>
            <a href="#">Szolgáltatások</a>
            <a href="#kapcsolat">Kapcsolat</a>
        </nav>

        <div class="header-actions">
            <button
                type="button"
                class="icon-btn"
                onclick="window.dispatchEvent(new CustomEvent('open-search'))"
                aria-label="Keresés megnyitása"
            >
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                    <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
                </svg>
            </button>

            <livewire:shop.cart.cart-counter :compact="true" />

            <a href="{{ route('cart.index') }}" class="btn-cta-primary">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/>
                </svg>
                <span>Ajánlatkérés</span>
            </a>
        </div>
    </div>
</header>
