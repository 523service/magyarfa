<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name') }} - CMS/e-Commerce</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,600;9..144,700;9..144,900&family=Outfit:wght@300;400;500;600&display=swap" rel="stylesheet">

    @vite('resources/css/app.css')
    <link rel="stylesheet" href="{{asset('css/style.css')}}?v{{filemtime(public_path('css/style.css'))}}">
</head>
<body>
    <!-- Navigation -->
    <div class="nav-container">
        <nav class="nav">
            <a href="/" class="logo">{{ config('app.name') }}</a>

            <div class="nav-menu">
                <a href="/" class="nav-link">Shop</a>
                <a href="/" class="nav-link">Collections</a>
                <a href="/" class="nav-link">About</a>
                <a href="/" class="nav-link">Contact</a>
            </div>

            <div class="nav-cta">
                @auth
                    <a href="/profile" class="btn btn-outline">Profile</a>
                    <form method="POST" action="{{ route('logout') }}" style="display: inline;">
                        @csrf
                        <button type="submit" class="btn btn-solid">Logout</button>
                    </form>
                @else
                    <a href="/login" class="btn btn-outline">Login</a>
                    <a href="/register" class="btn btn-solid">Register</a>
                @endauth
            </div>

            <div class="menu-toggle" onclick="toggleMenu()">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </nav>

        <div class="mobile-menu" id="mobileMenu">
            <nav>
                <a href="/" class="nav-link">Shop</a>
                <a href="/" class="nav-link">Collections</a>
                <a href="/" class="nav-link">About</a>
                <a href="/" class="nav-link">Contact</a>
                <div style="display: flex; flex-direction: column; gap: 0.75rem; margin-top: 1rem;">
                    @auth
                        <a href="/admin" class="btn btn-outline">Profile</a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="btn btn-solid" style="width: 100%;">Logout</button>
                        </form>
                    @else
                        <a href="/login" class="btn btn-outline">Login</a>
                        <a href="/register" class="btn btn-solid">Register</a>
                    @endauth
                </div>
            </nav>
        </div>
    </div>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content">
            <div class="hero-text">
                <h1>Curated Design for Modern Living</h1>
                <p>Discover thoughtfully selected products that blend timeless aesthetics with contemporary functionality. Each piece tells a story.</p>
                <div class="hero-cta">
                    <a href="#products" class="btn btn-solid">Explore Collection</a>
                    <a href="#categories" class="btn btn-outline">Shop by Category</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Categories Section -->
    @if($categories->count() > 0)
    <section class="categories" id="categories">
        <div class="section-header">
            <h2 class="section-title">Browse by Category</h2>
            <p class="section-subtitle">Find exactly what you're looking for</p>
        </div>

        <div class="category-grid">
            @foreach($categories as $category)
            <div class="category-card">
                <h3>{{ $category->name }}</h3>
                <p class="category-count">{{ $category->products_count }} {{ Str::plural('item', $category->products_count) }}</p>
            </div>
            @endforeach
        </div>
    </section>
    @endif

    <!-- Homepage Showcase Section -->
    @if($homepageProducts->count() > 0)
    <section class="products" id="products">
        <div class="section-header">
            <h2 class="section-title">Kiemelt ajánlataink</h2>
            <p class="section-subtitle">Válogatott termékek főoldalas bemutatóra</p>
        </div>

        <div class="product-grid">
            @foreach($homepageProducts as $product)
            <div class="product-card">
                <div class="product-image">
                    @php $imageUrl = $product->getMainImageUrl('thumb'); @endphp
                    @if($imageUrl)
                        <img src="{{ $imageUrl }}" alt="{{ $product->name }}">
                    @else
                        <div style="color: var(--color-forest); font-family: var(--font-display); font-size: 3rem;">
                            {{ substr($product->name, 0, 1) }}
                        </div>
                    @endif
                </div>
                <div class="product-info">
                    @if($product->brand)
                    <div class="product-brand">{{ $product->brand->name }}</div>
                    @endif
                    <h3 class="product-name">{{ $product->name }}</h3>
                    <div class="product-price">{{ number_format($product->price, 0, ',', ' ') }} Ft</div>
                </div>
            </div>
            @endforeach
        </div>
    </section>
    @endif

    <!-- Featured Products Section -->
    @if($featuredProducts->count() > 0)
    <section class="products" id="featured">
        <div class="section-header">
            <h2 class="section-title">Kiemelt termékek</h2>
            <p class="section-subtitle">Kézzel válogatott kedvencek legújabb kínálatunkból</p>
        </div>

        <div class="product-grid">
            @foreach($featuredProducts as $product)
            <div class="product-card">
                <div class="product-image">
                    @php $imageUrl = $product->getMainImageUrl('thumb'); @endphp
                    @if($imageUrl)
                        <img src="{{ $imageUrl }}" alt="{{ $product->name }}">
                    @else
                        <div style="color: var(--color-forest); font-family: var(--font-display); font-size: 3rem;">
                            {{ substr($product->name, 0, 1) }}
                        </div>
                    @endif
                    <span class="product-badge">Kiemelt</span>
                </div>
                <div class="product-info">
                    @if($product->brand)
                    <div class="product-brand">{{ $product->brand->name }}</div>
                    @endif
                    <h3 class="product-name">{{ $product->name }}</h3>
                    <div class="product-price">{{ number_format($product->price, 0, ',', ' ') }} Ft</div>
                </div>
            </div>
            @endforeach
        </div>
    </section>
    @endif

    <!-- Sale Products Section -->
    @if($saleProducts->count() > 0)
    <section class="products" id="akcio">
        <div class="section-header">
            <h2 class="section-title">Akciós termékek</h2>
            <p class="section-subtitle">Ne hagyd ki ezeket az ajánlatokat!</p>
        </div>

        <div class="product-grid">
            @foreach($saleProducts as $product)
            <div class="product-card">
                <div class="product-image">
                    @php $imageUrl = $product->getMainImageUrl('thumb'); @endphp
                    @if($imageUrl)
                        <img src="{{ $imageUrl }}" alt="{{ $product->name }}">
                    @else
                        <div style="color: var(--color-forest); font-family: var(--font-display); font-size: 3rem;">
                            {{ substr($product->name, 0, 1) }}
                        </div>
                    @endif
                    <span class="product-badge" style="background: #e53e3e;">Akció</span>
                </div>
                <div class="product-info">
                    @if($product->brand)
                    <div class="product-brand">{{ $product->brand->name }}</div>
                    @endif
                    <h3 class="product-name">{{ $product->name }}</h3>
                    <div class="product-price">
                        {{ number_format($product->price, 0, ',', ' ') }} Ft
                        @if($product->old_price && $product->old_price > $product->price)
                            <span style="text-decoration: line-through; color: #9ca3af; font-size: 0.85em; margin-left: 6px;">
                                {{ number_format($product->old_price, 0, ',', ' ') }} Ft
                            </span>
                        @endif
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </section>
    @endif

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-content">
            <div class="footer-logo">{{ config('app.name') }}</div>
            <p class="footer-text">Editorial ecommerce for the discerning customer</p>

            <div class="footer-links">
                <a href="/" class="footer-link">Shop</a>
                <a href="/" class="footer-link">About</a>
                <a href="/" class="footer-link">Contact</a>
                <a href="/admin" class="footer-link">Admin</a>
            </div>

            <div class="footer-bottom">
                <p>&copy; {{ date('Y') }} {{ config('app.name') }}. Crafted with attention to detail.</p>
            </div>
        </div>
    </footer>

    <script>
        function toggleMenu() {
            const toggle = document.querySelector('.menu-toggle');
            const menu = document.getElementById('mobileMenu');
            toggle.classList.toggle('active');
            menu.classList.toggle('active');
        }

        // Smooth scroll
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        });
    </script>
</body>
</html>
