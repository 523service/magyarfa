@php
    use Carbon\Carbon;

    $tz    = 'Europe/Budapest';
    $now   = Carbon::now($tz);
    $today = $now->toDateString();
    $time  = $now->format('H:i');
    $dow   = $now->dayOfWeekIso;
    $hours = config('shop.opening_hours');

    if (array_key_exists($today, $hours['special_days'] ?? [])) {
        $special = $hours['special_days'][$today];
        $isOpen = $special['hours'] !== null
            && $time >= trim(explode('–', $special['hours'])[0])
            && $time < trim(explode('–', $special['hours'])[1]);
    } elseif ($dow <= 5) {
        $isOpen = $time >= $hours['weekday']['open'] && $time < $hours['weekday']['close'];
    } elseif ($dow === 6) {
        $isOpen = $time >= $hours['saturday']['open'] && $time < $hours['saturday']['close'];
    } else {
        $isOpen = false;
    }

    $storeEmail = config('shop.store_email') ?: 'csomor@magyarfa.hu';
@endphp

<div class="topbar">
    <div class="container topbar-inner">
        <div class="topbar-left">
            <span class="topbar-item">
                <span class="topbar-item-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                </span>
                <span>2141 Csömör, Major út</span>
            </span>
            <span class="topbar-item">
                <span class="topbar-item-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92Z"/></svg>
                </span>
                <a href="tel:+36709411982">+36-70-941-1982</a>
            </span>
            <span class="topbar-item">
                <span class="topbar-item-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16v16H4z" fill="none"/><path d="m22 6-10 7L2 6"/><path d="M2 6h20v12H2z"/></svg>
                </span>
                <a href="mailto:{{ $storeEmail }}">{{ $storeEmail }}</a>
            </span>
        </div>

        <div class="topbar-right">
            <div
                class="header-block--hours"
                x-data="{ open: false }"
                @click="open = !open"
                @click.outside="open = false"
            >
                <span class="hours-trigger">
                    <span class="hours-dot {{ $isOpen ? 'hours-dot--open' : 'hours-dot--closed' }}"></span>
                    <span>{{ $isOpen ? 'Nyitva' : 'Zárva' }}</span>
                </span>
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
                </div>
            </div>

            <div class="topbar-divider"></div>

            <livewire:shop.cart.cart-counter />

            <div class="topbar-divider"></div>

            @auth
                <a href="{{ route('profile.edit') }}" class="topbar-item">Fiókom</a>
            @else
                <a href="{{ route('login') }}" class="topbar-item">Belépés</a>
                <a href="{{ route('register') }}" class="topbar-item">Regisztráció</a>
            @endauth
        </div>
    </div>
</div>
