<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Magyar Fa - Faanyag kereskedés Csömör' }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:300,400,500,600,700|barlow-condensed:600,700,800" rel="stylesheet" />

    <!-- Styles -->
    @vite(['resources/css/app.css', 'resources/css/shop.css', 'resources/js/app.js'])
    @livewireStyles

    @stack('styles')
    @include('partials.shop.google-tag')
</head>
<body class="font-['Inter'] bg-[var(--bg)] text-[var(--text-main)]">

    @include('partials.shop.topbar')

    @include('partials.shop.header')

    <main class="container mx-auto max-w-[1280px] px-4">
        {{ $slot }}
    </main>

    @include('partials.shop.footer')

    {{-- Search overlay rendered outside <header> to avoid backdrop-filter stacking context bug --}}
    @include('partials.shop.search-overlay')

    <livewire:shop.cart.cart-modal />

    @include('partials.shop.bottom-nav')

    <livewire:feedback-button />

    @livewireScripts
    @stack('scripts')
</body>
</html>
