<section class="hero">

    <div class="hero__content">
        <div class="hero-badges">
            <span class="badge-soft">Interaktív hőszigetelő rendszer</span>
        </div>
        <h1>Homlokzati, födém és lábazati hőszigetelés egy helyen</h1>
        <p class="hero__lead">
            Válaszd ki a felhasználási módot, add meg a m²-t, a rendszer pedig kiszámolja,
            mennyi hőszigetelő anyagra, ragasztóra és kiegészítőre lesz szükséged.
        </p>
        <div class="hero__actions">
            <a href="{{ route('home') }}" class="btn-primary">
                <span>Ajánlatot kérek</span> →
            </a>
        </div>

        <div class="hero__meta">
            <span>8 hónapos garancia</span>
            <span>60 nap díjmentes tárolás</span>
            <span>&gt; 10 000 elégedett ügyfél</span>
            <span>&gt; 120 000 m² kiszállítva</span>
        </div>
    </div>

    <div class="hero__visual">
        <div class="stack-wrap" id="heroStackWrap">
            <img
                class="stack-image"
                src="{{ asset('img/hero-hotspot-415.webp') }}"
                alt="Hőszigetelő rendszer rétegrend"
            />

            {{-- Hotspots --}}
            {{--<button class="hotspot" style="left: 17%; top: 33%;" data-card="hs-profile"  aria-label="Falazat"></button>--}}
            <button class="hotspot" style="left: 24%; top: 41%;" data-card="hs-adhesive" aria-label="Ragasztó"></button>
            <button class="hotspot" style="left: 31%; top: 54%;" data-card="hs-board"    aria-label="Hőszigetelő lemez"></button>
            <button class="hotspot" style="left: 32%; top: 74%;" data-card="hs-dubel"    aria-label="Dűbel"></button>
            <button class="hotspot" style="left: 25%; top: 79%;" data-card="hs-indito"    aria-label="Indítóprofil"></button>
            <button class="hotspot" style="left: 37%; top: 61%;" data-card="hs-basecoat" aria-label="Ágyazó ragasztó"></button>
            <button class="hotspot" style="left: 49%; top: 59%;" data-card="hs-basecoat-2" aria-label="Ágyazó ragasztó 2"></button>
            <button class="hotspot" style="left: 41%; top: 50%;" data-card="hs-mesh"     aria-label="Üvegszövet háló"></button>
            <button class="hotspot" style="left: 59%; top: 60%;" data-card="hs-primer"   aria-label="Alapozó"></button>
            <button class="hotspot" style="left: 68%; top: 55%;" data-card="hs-plaster"  aria-label="Vékonyvakolat"></button>

            {{-- Popup cards --}}
            <div id="hs-profile" class="hotspot-card" style="left: 6%; top: 14%;">
                <h3>Indító profil</h3>
                <p>Alsó vezetőelem, ami pontos indulást ad a rendszernek és segít a síktartásban.</p>
                <a href="/kategoria/labazati-inditoprofil">Kategória megnyitása →</a>
            </div>
            <div id="hs-adhesive" class="hotspot-card" style="left: 13%; top: 10%;">
                <h3>Ragasztó</h3>
                <p>A hőszigetelő lap falhoz történő rögzítésének elsődleges rétege. Rendszerfüggő anyag.</p>
                <a href="/kategoria/ragaszto">Kategória megnyitása →</a>
            </div>
            <div id="hs-board" class="hotspot-card" style="left: 27%; top: 22%;">
                <h3>Hőszigetelő lemez</h3>
                <p>EPS vagy grafitos lap, ami a rendszer fő hőtechnikai eleme és a legfontosabb vastagsági döntés.</p>
                <a href="/kategoria/hoszigeteles">Kategória megnyitása →</a>
            </div>
            <div id="hs-dubel" class="hotspot-card" style="left: 34%; top: 53%;">
                <h3>Dűbel</h3>
                <p>Mechanikai rögzítés a stabilitásért, különösen magasabb épületeknél vagy kritikus felületeknél.</p>
                <a href="/kategoria/tarcsas-dubel">Kategória megnyitása →</a>
            </div>
            <div id="hs-indito" class="hotspot-card" style="left: 1%; top: 53%;">
                <h3>Indítóprofil, kezdősín</h3>
                <p>Alsó vezetőelem, ami pontos indulást ad a rendszernek és segít a síktartásban.</p>
                <a href="/kategoria/labazati-inditoprofil">Kategória megnyitása →</a>
            </div>
            <div id="hs-basecoat" class="hotspot-card" style="right: 31%; top: 61%;">
                <h3>Ágyazó ragasztó</h3>
                <p>Ez fogadja a hálót, és létrehozza a teherelosztó, erősített alapréteget.</p>
                <a href="/kategoria/ragaszto">Kategória megnyitása →</a>
            </div>
            <div id="hs-basecoat-2" class="hotspot-card" style="right: 24%; top: 28%;">
                <h3>Ágyazó ragasztó 2</h3>
                <p>Ez fogadja a hálót, és létrehozza a teherelosztó, erősített alapréteget.</p>
                <a href="{{ route('home') }}">Kategória megnyitása →</a>
            </div>
            <div id="hs-mesh" class="hotspot-card" style="right: 28%; top: 22%;">
                <h3>Üvegszövet háló</h3>
                <p>Repedésáthidaló erősítőréteg, ami beágyazva dolgozik együtt az alapréteggel.</p>
                <a href="/kategoria/uvegszovet-halo">Kategória megnyitása →</a>
            </div>

            <div id="hs-primer" class="hotspot-card" style="right: 16%; top: 28%;">
                <h3>Alapozó</h3>
                <p>Tapadást és egységes felületet biztosít a végső vakolatréteg előtt.</p>
                <a href="/kategoria/vakolatalapozo">Kategória megnyitása →</a>
            </div>

            <div id="hs-plaster" class="hotspot-card" style="right: 2%; top: 29%;">
                <h3>Vékonyvakolat</h3>
                <p>A látható záróréteg, ami egyszerre ad esztétikát és időjárásálló külső felületet.</p>
                <a href="/kategoria/vakolatok">Kategória megnyitása →</a>
            </div>

            <div class="hero__legend">
                <div class="hero__legend-item">EPS alaprendszer</div>
                <div class="hero__legend-item">EPS grafitos alaprendszer</div>
                <div class="hero__legend-item">EPS komplett rendszer</div>
            </div>

        </div>
    </div>
</section>

@push('scripts')
<script>
(function () {
    const hotspots = document.querySelectorAll('.hotspot');
    const cards    = document.querySelectorAll('.hotspot-card');

    function closeAll() {
        hotspots.forEach(h => h.classList.remove('is-active'));
        cards.forEach(c => c.classList.remove('is-visible'));
    }

    hotspots.forEach(function (hotspot) {
        const card = document.getElementById(hotspot.dataset.card);
        if (!card) { return; }

        function open() {
            closeAll();
            hotspot.classList.add('is-active');
            card.classList.add('is-visible');
        }

        hotspot.addEventListener('mouseenter', open);
        hotspot.addEventListener('focus', open);
        hotspot.addEventListener('click', function (e) {
            e.stopPropagation();
            if (hotspot.classList.contains('is-active')) {
                const link = card.querySelector('a');
                if (link) { window.location.href = link.href; }
                return;
            }
            open();
        });
    });

    document.addEventListener('click', function (e) {
        if (!e.target.closest('.hotspot') && !e.target.closest('.hotspot-card')) {
            closeAll();
        }
    });

    const visual = document.querySelector('.hero__visual');
    if (visual) {
        visual.addEventListener('mouseleave', closeAll);
    }
}());
</script>
@endpush
