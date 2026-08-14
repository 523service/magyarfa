<header class="header">
    <div class="container header-inner">

        @php
            use Carbon\Carbon;

            $tz       = 'Europe/Budapest';
            $now      = Carbon::now($tz);
            $today    = $now->toDateString();
            $time     = $now->format('H:i');
            $dow      = $now->dayOfWeekIso; // 1=H … 6=SZ, 7=V
            $hours    = config('shop.opening_hours');
            $special  = $hours['special_days'] ?? [];

            // Jövőbeli speciális napok (legfeljebb 3, ma után)
            $upcomingSpecial = collect($special)
                ->filter(fn ($v, $date) => $date >= $today)
                ->sortKeys()
                ->take(3);

            // Nyitva van-e most?
            if (array_key_exists($today, $special)) {
                $todaySpecial = $special[$today];
                if ($todaySpecial['hours'] === null) {
                    $isOpen = false;
                } else {
                    [$openT, $closeT] = explode('–', $todaySpecial['hours']);
                    $isOpen = $time >= trim($openT) && $time < trim($closeT);
                }
            } elseif ($dow <= 5) {
                // H–P
                $isOpen = $time >= $hours['weekday']['open'] && $time < $hours['weekday']['close'];
            } elseif ($dow === 6) {
                // Szombat
                $isOpen = $time >= $hours['saturday']['open'] && $time < $hours['saturday']['close'];
            } else {
                // Vasárnap
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
                <a href="tel:+36709411982" class="mobile-menu-item">
                    <span class="mobile-menu-icon">☎</span>
                    <span>+36-70-941-1982</span>
                </a>
                <a href="tel:+36203984324" class="mobile-menu-item">
                    <span class="mobile-menu-icon">☎</span>
                    <span>+36-20-398-4324</span>
                </a>
                <div class="mobile-menu-item mobile-menu-item--plain">
                    <span class="mobile-menu-icon">🚚</span>
                    <span>Országos kiszállítás</span>
                </div>
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

        <div class="logo-group">
            <a href="{{ route('home') }}" class="logo">
                <img src="/img/magyarszigeteles.webp" alt="MagyarSzigetelés.hu" class="logo-img">
            </a>

            <a href="{{ route('home') }}" class="">
                <div class="logo-brand-name">
                    <span>MAGYAR</span>
                    <span>SZIGETELÉS</span>
                </div>
            </a>
        </div>

        <div
            class="header-block header-block--hours"
            x-data="{ open: false }"
            @click="open = !open"
            @click.outside="open = false"
        >
                <span class="header-block-label">Nyitvatartás</span>
                <span class="header-block-value hours-trigger mt-1">
                    <span class="hours-dot {{ $isOpen ? 'hours-dot--open' : 'hours-dot--closed' }}"></span>
                    <span>{{ $isOpen ? 'Nyitva' : 'Zárva' }}</span>
                    <svg class="hours-chevron" :class="{ 'hours-chevron--open': open }" width="12" height="12" viewBox="0 0 12 12" fill="none" aria-hidden="true">
                        <path d="M2 4l4 4 4-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </span>

                {{-- Popover panel --}}
                <div
                    class="hours-popover"
                    x-show="open"
                    x-transition:enter="hours-popover-enter"
                    x-transition:enter-start="hours-popover-enter-start"
                    x-transition:enter-end="hours-popover-enter-end"
                    x-transition:leave="hours-popover-enter"
                    x-transition:leave-start="hours-popover-enter-end"
                    x-transition:leave-end="hours-popover-enter-start"
                    @click.stop
                >
                    <table class="hours-table">
                        <tbody>
                            <tr>
                                <td class="hours-day">H – P</td>
                                <td class="hours-time">{{ $hours['weekday']['open'] }}–{{ $hours['weekday']['close'] }}</td>
                            </tr>
                            <tr>
                                <td class="hours-day">Szombat</td>
                                <td class="hours-time">{{ $hours['saturday']['open'] }}–{{ $hours['saturday']['close'] }}</td>
                            </tr>
                            <tr>
                                <td class="hours-day">Vasárnap</td>
                                <td class="hours-time hours-time--closed">Zárva</td>
                            </tr>
                        </tbody>
                    </table>

                    @if ($upcomingSpecial->isNotEmpty())
                        <div class="hours-special-header">Közelgő ünnepnapok</div>
                        <table class="hours-table">
                            <tbody>
                                @foreach ($upcomingSpecial as $date => $info)
                                    @php
                                        $d = Carbon::parse($date, $tz);
                                        $dateLabel = $d->format('M\. j\.');
                                    @endphp
                                    <tr>
                                        <td class="hours-day">
                                            {{ $info['label'] }}
                                            <span class="hours-date">{{ $dateLabel }}</span>
                                        </td>
                                        <td class="hours-time {{ $info['hours'] === null ? 'hours-time--closed' : '' }}">
                                            {{ $info['hours'] ?? 'Zárva' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            </div>

        {{-- Search trigger — dispatches window event picked up by the overlay panel in the layout --}}
        <button
            type="button"
            class="search-trigger header-search"
            onclick="window.dispatchEvent(new CustomEvent('open-search'))"
            aria-label="Keresés megnyitása"
        >
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
            </svg>
            <span class="search-trigger-text">Keresés termékek, kategóriák között...</span>
        </button>

        <div class="header-meta">
            <div class="header-block">
                <span class="header-block-label">Szakértői segítség</span>
                <span class="header-block-value">Hívj minket bátran!</span>
            </div>

            <livewire:shop.cart.cart-counter />
        </div>
    </div>
</header>

