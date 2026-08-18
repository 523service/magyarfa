<section class="hero" style="background-image: linear-gradient(90deg, rgba(11,41,24,0.92) 0%, rgba(11,41,24,0.72) 42%, rgba(11,41,24,0.35) 75%, rgba(11,41,24,0.15) 100%), url('{{ asset('img/banner/mfa-banner-01.webp') }}');">
    <div class="hero-content">
        <div class="hero-badges">
            <span class="badge-soft">Prémium fatelep Csömörön</span>
        </div>
        <h1>Minőségi faanyag, megbízható forrásból</h1>
        <p class="hero-lead">
            Fenyő fűrészáru, gyalult fáru és OSB lemezek széles választéka.
            Építsen velünk – tartósan.
        </p>
        <div class="hero-actions">
            <a href="{{ route('home') }}#termekek" class="btn-cta-primary">
                <span>Termékeink</span> →
            </a>
            <a href="{{ route('cart.index') }}" class="btn-cta-secondary">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/>
                </svg>
                <span>Ajánlatkérés</span>
            </a>
        </div>
    </div>

    <div class="hero-stamp" aria-hidden="true">
        <svg viewBox="0 0 200 200">
            <defs>
                <path id="hero-stamp-circle" d="M 100,100 m -78,0 a 78,78 0 1,1 156,0 a 78,78 0 1,1 -156,0" />
            </defs>
            <circle cx="100" cy="100" r="96" class="hero-stamp-ring" />
            <circle cx="100" cy="100" r="66" class="hero-stamp-ring hero-stamp-ring--inner" />
            <text class="hero-stamp-text">
                <textPath href="#hero-stamp-circle" startOffset="0%">
                    MAGYAR FA • CSÖMÖR • MAJOR ÚT •
                </textPath>
            </text>
            <text class="hero-stamp-text">
                <textPath href="#hero-stamp-circle" startOffset="50%">
                    MINŐSÉG • TARTÓSSÁG •
                </textPath>
            </text>
            <g class="hero-stamp-icon" transform="translate(100 100)">
                <path d="M0 -30 L12 -8 H6 L16 10 H8 L18 26 H4 V38 H-4 V26 H-18 L-8 10 H-16 L-6 -8 H-12 Z" transform="scale(0.6)" />
            </g>
        </svg>
    </div>
</section>
