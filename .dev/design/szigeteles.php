<!doctype html>
<html lang="hu">
<head>
    <meta charset="utf-8">
    <title>MagyarSzigeteles.hu – Hőszigetelés webshop</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Google font opcionális, nyugodtan kidobhatod -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:300,400,500,600,700" rel="stylesheet" />

    <style>
        :root {
            --bg: #f3f5f4;
            --card-bg: #ffffff;
            --accent: #2f855a;
            --accent-soft: #e6f4ec;
            --accent-dark: #22543d;
            --text-main: #1a202c;
            --text-muted: #6b7280;
            --border-subtle: #e2e8f0;
            --radius-lg: 14px;
            --radius-md: 10px;
            --shadow-sm: 0 8px 20px rgba(15, 23, 42, 0.06);
        }

        * {
            box-sizing: border-box;
        }

        html, body {
            margin: 0;
            padding: 0;
        }

        body {
            font-family: "Inter", system-ui, -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--bg);
            color: var(--text-main);
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 16px;
        }

        /* Topbar */

        .topbar {
            background: #111827;
            color: #e5e7eb;
            font-size: 13px;
        }

        .topbar-inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 6px 16px;
            gap: 12px;
        }

        .topbar-left,
        .topbar-right {
            display: flex;
            align-items: center;
            gap: 18px;
            flex-wrap: wrap;
        }

        .topbar-item {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            opacity: 0.9;
        }

        .topbar-item-icon {
            width: 18px;
            height: 18px;
            border-radius: 999px;
            border: 1px solid rgba(249,250,251,0.25);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
        }

        /* Header */

        .header {
            background: #ffffff;
            box-shadow: var(--shadow-sm);
        }

        .header-inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 16px;
            gap: 24px;
        }

        .logo {
            font-weight: 700;
            font-size: 22px;
            letter-spacing: 0.03em;
            display: flex;
            align-items: baseline;
            gap: 4px;
        }

        .logo span {
            color: var(--accent);
        }

        .header-meta {
            display: flex;
            align-items: center;
            gap: 24px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .header-block {
            display: flex;
            flex-direction: column;
            font-size: 13px;
            line-height: 1.3;
        }

        .header-block-label {
            text-transform: uppercase;
            letter-spacing: 0.12em;
            font-size: 11px;
            color: var(--text-muted);
        }

        .header-block-value {
            font-weight: 500;
        }

        .cart-chip {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            border-radius: 999px;
            background: var(--accent-soft);
            font-size: 13px;
        }

        .cart-chip-icon {
            width: 24px;
            height: 24px;
            border-radius: 999px;
            background: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
        }

        /* Layout */

        .layout {
            display: grid;
            grid-template-columns: 260px minmax(0, 1fr);
            gap: 24px;
            padding: 24px 16px 40px;
        }

        /* Sidebar */

        .sidebar {
            align-self: flex-start;
            position: sticky;
            top: 12px;
        }

        .sidebar-card {
            background: var(--card-bg);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
            padding: 16px;
            margin-bottom: 16px;
        }

                /* Sidebar category groups */

        .sidebar-title {
            font-size: 14px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.11em;
            color: var(--text-muted);
            margin: 0 0 10px;
        }

        .category-group {
            border-radius: 10px;
            margin-bottom: 6px;
        }

        .category-group-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 8px 10px;
            border-radius: 10px;
            cursor: pointer;
            font-size: 14px;
        }

        .category-group-header:hover {
            background: #f3f4ff;
        }

        .category-group-header-title {
            font-weight: 500;
        }

        .category-group-icon {
            font-size: 11px;
            opacity: 0.7;
            transition: transform 0.2s ease;
        }

        .category-group.open .category-group-header {
            background: var(--accent-soft);
            color: var(--accent-dark);
        }

        .category-group.open .category-group-icon {
            transform: rotate(180deg);
        }

        .submenu {
            list-style: none;
            padding: 0 0 6px 14px;
            margin: 0;
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.22s ease;
        }

        .category-group.open .submenu {
            max-height: 400px; /* enough for all items */
        }

        .submenu li {
            padding: 4px 4px;
            font-size: 13px;
            color: var(--text-muted);
            border-radius: 8px;
            cursor: pointer;
        }

        .submenu li:hover {
            background: #f3f4ff;
            color: var(--text-main);
        }

        /* Simple lists for brands and glossary */

        .simple-list {
            list-style: none;
            margin: 0;
            padding: 0;
            font-size: 13px;
        }

        .simple-list li {
            padding: 4px 0;
            color: var(--text-main);
        }

        .simple-list li + li {
            border-top: 1px solid rgba(226,232,240,0.7);
        }

        .simple-list li span {
            display: inline-block;
            padding: 3px 0;
        }

        /* Content */

        .content {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        /* Hero */

        .hero {
            background: radial-gradient(circle at top left, #e5f3ff 0, #ffffff 35%, #f3f5f4 100%);
            border-radius: var(--radius-lg);
            padding: 20px 20px 18px;
            display: grid;
            grid-template-columns: minmax(0, 2.1fr) minmax(0, 2.3fr);
            gap: 16px;
            box-shadow: var(--shadow-sm);
        }

        .hero-main h1 {
            margin: 0 0 6px;
            font-size: 24px;
        }

        .hero-main p {
            margin: 0 0 12px;
            color: var(--text-muted);
            font-size: 14px;
        }

        .hero-badges {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 12px;
        }

        .badge-soft {
            font-size: 11px;
            padding: 4px 8px;
            border-radius: 999px;
            background: rgba(34, 197, 94, 0.08);
            color: var(--accent-dark);
            text-transform: uppercase;
            letter-spacing: 0.12em;
        }

        .btn-primary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 9px 14px;
            border-radius: 999px;
            background: var(--accent);
            color: #ffffff;
            font-size: 13px;
            font-weight: 500;
            border: none;
            cursor: pointer;
            gap: 6px;
            box-shadow: 0 6px 18px rgba(34,197,94,0.24);
        }

        .btn-primary span {
            font-size: 15px;
        }

        .hero-stats {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
        }

        .hero-stat-card {
            background: rgba(255,255,255,0.88);
            border-radius: 12px;
            padding: 10px 10px 9px;
            border: 1px solid rgba(226,232,240,0.9);
            font-size: 12px;
        }

        .hero-stat-label {
            color: var(--text-muted);
            font-size: 11px;
            margin-bottom: 2px;
        }

        .hero-stat-value {
            font-weight: 600;
        }

        /* Banner / video placeholder */

        .banner {
            background: #111827;
            border-radius: var(--radius-lg);
            padding: 14px 16px;
            color: #f9fafb;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .banner-text {
            font-size: 14px;
        }

        .banner-text strong {
            display: block;
            font-size: 15px;
            margin-bottom: 1px;
        }

        .banner-pill {
            font-size: 11px;
            padding: 4px 10px;
            border-radius: 999px;
            border: 1px solid rgba(249,250,251,0.2);
            opacity: 0.9;
        }

        /* Toolbar */

        .product-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            font-size: 13px;
        }

        .toolbar-left {
            color: var(--text-muted);
        }

        .toolbar-right {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .select-input {
            border-radius: 999px;
            border: 1px solid var(--border-subtle);
            padding: 6px 10px;
            font-size: 13px;
            background: #ffffff;
        }

        /* Product grid */

        .product-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 16px;
        }

        .product-card {
            background: var(--card-bg);
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-sm);
            padding: 12px 12px 11px;
            display: flex;
            flex-direction: column;
        }

        .product-image {
            width: 100%;
            aspect-ratio: 4 / 3;
            border-radius: 10px;
            background: #e5e7eb;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            color: #9ca3af;
            margin-bottom: 10px;
        }

        .product-title {
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 4px;
            min-height: 38px;
        }

        .product-meta {
            font-size: 12px;
            color: var(--text-muted);
            margin-bottom: 8px;
        }

        .product-footer {
            margin-top: auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
        }

        .product-price {
            font-weight: 600;
            font-size: 15px;
        }

        .product-price small {
            display: block;
            font-size: 11px;
            color: var(--text-muted);
            font-weight: 400;
        }

        .btn-outline {
            border-radius: 999px;
            border: 1px solid var(--accent);
            color: var(--accent-dark);
            padding: 7px 11px;
            font-size: 13px;
            background: #ffffff;
            cursor: pointer;
            white-space: nowrap;
        }

        /* Footer */

        .footer {
            border-top: 1px solid var(--border-subtle);
            font-size: 12px;
            color: var(--text-muted);
            padding: 18px 16px 26px;
        }

        /* Responsive */

        @media (max-width: 1024px) {
            .layout {
                grid-template-columns: minmax(0, 1fr);
            }
            .sidebar {
                position: static;
                order: 2;
            }
            .hero {
                grid-template-columns: minmax(0, 1fr);
            }
            .hero-stats {
                grid-template-columns: repeat(4, minmax(0, 1fr));
            }
        }

        @media (max-width: 768px) {
            .topbar-inner,
            .header-inner {
                flex-direction: column;
                align-items: flex-start;
            }

            .layout {
                padding-inline: 12px;
            }

            .product-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .hero-stats {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 520px) {
            .product-grid {
                grid-template-columns: minmax(0, 1fr);
            }

            .banner {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>

    <!-- Topbar -->
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
                <div class="topbar-item">
                    <span>Belépés</span>
                </div>
                <div class="topbar-item">
                    <span>Regisztráció</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Header -->
    <header class="header">
        <div class="container header-inner">
            <a href="#" class="logo">
                Magyar<span>Szigetelés</span>.hu
            </a>

            <div class="header-meta">
                <div class="header-block">
                    <span class="header-block-label">Nyitvatartás</span>
                    <span class="header-block-value">H–P: 7:00–17:00</span>
                </div>
                <div class="header-block">
                    <span class="header-block-label">Szakértői segítség</span>
                    <span class="header-block-value">Hívj minket bátran!</span>
                </div>
                <div class="cart-chip">
                    <div class="cart-chip-icon">🛒</div>
                    <div>
                        <div style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.14em;">Kosár</div>
                        <div style="font-size: 13px; font-weight: 500;">0 termék – 0 Ft</div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Main layout -->
    <main class="container layout">

        <!-- Sidebar -->
                <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-card">
                <h2 class="sidebar-title">Termék kategóriák</h2>

                <!-- Polisztirol -->
                <div class="category-group open">
                    <div class="category-group-header">
                        <span class="category-group-header-title">Polisztirol termékek</span>
                        <span class="category-group-icon">▾</span>
                    </div>
                    <ul class="submenu">
                        <li>Polisztirol rendszer</li>
                        <li>EPS 80 homlokzati lemez</li>
                        <li>EPS G 80 homlokzati lemez</li>
                        <li>EPS 100 lépésálló lemez</li>
                        <li>XPS lábazati lemez</li>
                    </ul>
                </div>

                <!-- Ásványgyapot -->
                <div class="category-group">
                    <div class="category-group-header">
                        <span class="category-group-header-title">Ásványgyapot termékek</span>
                        <span class="category-group-icon">▾</span>
                    </div>
                    <ul class="submenu">
                        <li>Kőzetgyapot rendszer</li>
                        <li>Kőzetgyapot tábla</li>
                        <li>Üveggyapot tekercs</li>
                    </ul>
                </div>

                <!-- Vakolatok -->
                <div class="category-group">
                    <div class="category-group-header">
                        <span class="category-group-header-title">Vakolatok</span>
                        <span class="category-group-icon">▾</span>
                    </div>
                    <ul class="submenu">
                        <li>Homlokzati vakolat</li>
                        <li>Lábazati vakolat</li>
                        <li>Vakolatalapozó</li>
                    </ul>
                </div>

                <!-- Kiegészítők -->
                <div class="category-group">
                    <div class="category-group-header">
                        <span class="category-group-header-title">Kiegészítők</span>
                        <span class="category-group-icon">▾</span>
                    </div>
                    <ul class="submenu">
                        <li>Ragasztó</li>
                        <li>Üvegszövet háló</li>
                        <li>Tárcsás dűbel</li>
                        <li>Élvédő</li>
                        <li>Lábazati indítóprofil</li>
                    </ul>
                </div>
            </div>

            <!-- Gyártók külön card -->
            <div class="sidebar-card">
                <h2 class="sidebar-title">Gyártók</h2>
                <ul class="simple-list">
                    <li><span>Caparol</span></li>
                    <li><span>Cemix</span></li>
                    <li><span>Knauf</span></li>
                    <li><span>Mapei</span></li>
                    <li><span>Meton</span></li>
                    <li><span>Revco</span></li>
                    <li><span>Rockwool</span></li>
                    <li><span>Ursa</span></li>
                </ul>
            </div>

            <!-- Fogalmak külön card -->
            <div class="sidebar-card">
                <h2 class="sidebar-title">Fogalmak</h2>
                <ul class="simple-list">
                    <li><span>Hungarocell</span></li>
                    <li><span>Polisztirol</span></li>
                    <li><span>Homlokzati hőszigetelés</span></li>
                    <li><span>Grafitos hőszigetelés</span></li>
                    <li><span>Lépésálló szigetelés</span></li>
                    <li><span>Lábazati hőszigetelés</span></li>
                    <li><span>Ásványgyapot</span></li>
                    <li><span>Kőzetgyapot</span></li>
                    <li><span>Üveggyapot</span></li>
                </ul>
            </div>
        </aside>


        <!-- Content -->
        <section class="content">

            <!-- Hero -->
            <section class="hero">
                <div class="hero-main">
                    <div class="hero-badges">
                        <span class="badge-soft">Hőszigetelés szakértő webáruház</span>
                    </div>
                    <h1>Homlokzati, födém és lábazati hőszigetelés egy helyen</h1>
                    <p>
                        Válaszd ki a felhasználási módot, add meg a m²-t, a rendszer pedig kiszámolja,
                        mennyi hőszigetelő anyagra, ragasztóra és kiegészítőre lesz szükséged.
                    </p>
                    <button class="btn-primary">
                        <span>Ajánlatot kérek</span> →
                    </button>
                </div>
                <div class="hero-stats">
                    <div class="hero-stat-card">
                        <div class="hero-stat-label">Visszavásárlás</div>
                        <div class="hero-stat-value">8 hónapos garancia</div>
                    </div>
                    <div class="hero-stat-card">
                        <div class="hero-stat-label">Tárolás</div>
                        <div class="hero-stat-value">60 nap díjmentesen</div>
                    </div>
                    <div class="hero-stat-card">
                        <div class="hero-stat-label">Vásárlók</div>
                        <div class="hero-stat-value">&gt; 10 000 elégedett ügyfél</div>
                    </div>
                    <div class="hero-stat-card">
                        <div class="hero-stat-label">Kiszállított m²</div>
                        <div class="hero-stat-value">&gt; 120 000 m² hőszigetelés</div>
                    </div>
                </div>
            </section>

            <!-- Banner / video -->
            <section class="banner">
                <div class="banner-text">
                    <strong>Bemutatkozó videó</strong>
                    <span>Ismerd meg, hogyan segítünk a megfelelő hőszigetelő rendszer kiválasztásában.</span>
                </div>
                <div class="banner-pill">
                    Itt lesz a YouTube beágyazás / hero kép
                </div>
            </section>

            <!-- Toolbar -->
            <section class="product-toolbar">
                <div class="toolbar-left">
                    32 termék egy kategóriában – grafitos és fehér EPS rendszerek.
                </div>
                <div class="toolbar-right">
                    <select class="select-input">
                        <option>Rendezés: Ajánlott</option>
                        <option>Ár szerint növekvő</option>
                        <option>Ár szerint csökkenő</option>
                    </select>
                    <select class="select-input">
                        <option>24 / oldal</option>
                        <option>48 / oldal</option>
                        <option>96 / oldal</option>
                    </select>
                </div>
            </section>

            <!-- Product grid -->
            <section class="product-grid">
                <!-- 1. termékkártya -->
                <article class="product-card">
                    <div class="product-image">
                        Termékkép
                    </div>
                    <div class="product-title">
                        Grafitos EPS homlokzati hőszigetelő lap 10 cm (m² / csomag)
                    </div>
                    <div class="product-meta">
                        λ = 0,031 W/mK • 10 cm • 1 csomag = 6 m²
                    </div>
                    <div class="product-footer">
                        <div class="product-price">
                            3 850 Ft
                            <small>bruttó / m²</small>
                        </div>
                        <button class="btn-outline">Kosárba</button>
                    </div>
                </article>

                <!-- 2. termékkártya -->
                <article class="product-card">
                    <div class="product-image">
                        Termékkép
                    </div>
                    <div class="product-title">
                        EPS 80 fehér homlokzati hőszigetelő lap 10 cm
                    </div>
                    <div class="product-meta">
                        λ = 0,038 W/mK • 1 csomag = 6 m²
                    </div>
                    <div class="product-footer">
                        <div class="product-price">
                            2 990 Ft
                            <small>bruttó / m²</small>
                        </div>
                        <button class="btn-outline">Kosárba</button>
                    </div>
                </article>

                <!-- 3. termékkártya -->
                <article class="product-card">
                    <div class="product-image">
                        Termékkép
                    </div>
                    <div class="product-title">
                        Komplett homlokzati rendszer (EPS 10 cm + ragasztó + háló)
                    </div>
                    <div class="product-meta">
                        Ideális családi házak homlokzati szigeteléséhez.
                    </div>
                    <div class="product-footer">
                        <div class="product-price">
                            8 490 Ft
                            <small>bruttó / m²</small>
                        </div>
                        <button class="btn-outline">Kosárba</button>
                    </div>
                </article>

                <!-- add more product cards as needed -->
            </section>
        </section>
    </main>

    <!-- Footer -->
    <footer class="footer container">
        © MagyarSzigeteles.hu – Hőszigetelés webshop. Minden jog fenntartva.
    </footer>

</body>
</html>
