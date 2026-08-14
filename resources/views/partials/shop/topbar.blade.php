<div class="topbar">
    <div class="container topbar-inner">
        <div class="topbar-left">
            <div class="topbar-item">
                <span class="topbar-item-icon">☎</span>
                <span><strong><a href="tel:+36709411982">+36-70-941-1982</a></strong> | <strong><a href="tel:+36203984324">+36-20-398-4324</a></strong></span>
            </div>
            <div class="topbar-item">
                <span class="topbar-item-icon">🚚</span>
                <span>Országos kiszállítás</span>
            </div>
        </div>
        <div class="topbar-right">
            @auth
                <a href="{{ route('profile.edit') }}" class="topbar-item">
                    <span>Fiókom</span>
                </a>
                <form method="POST" action="{{ route('logout') }}" class="inline">
                    @csrf
                    <button type="submit" class="topbar-item border-none bg-transparent cursor-pointer">
                        <span>Kilépés</span>
                    </button>
                </form>
            @else
                <a href="{{ route('login') }}" class="topbar-item">
                    <span>Belépés</span>
                </a>
                <a href="{{ route('register') }}" class="topbar-item">
                    <span>Regisztráció</span>
                </a>
            @endauth
        </div>
    </div>
</div>
