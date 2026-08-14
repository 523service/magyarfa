<x-layouts.shop-auth>
    <x-slot name="title">Regisztráció - MagyarSzigetelés.hu</x-slot>

    <div class="text-center mb-6">
        <h1 class="text-2xl font-bold text-[var(--text-main)]">Regisztráció</h1>
        <p class="text-sm text-[var(--text-muted)] mt-2">Hozz létre egy új fiókot</p>
    </div>

    <form method="POST" action="{{ route('register') }}">
        @csrf

        <!-- Name -->
        <div class="mb-4">
            <label for="name" class="block text-sm font-medium text-[var(--text-main)] mb-1">
                Név
            </label>
            <input
                id="name"
                type="text"
                name="name"
                value="{{ old('name') }}"
                required
                autofocus
                autocomplete="name"
                class="w-full px-4 py-3 rounded-[var(--radius-md)] border border-[var(--border-subtle)] bg-white text-[var(--text-main)] placeholder-[var(--text-muted)] focus:outline-none focus:border-[var(--accent)] focus:ring-1 focus:ring-[var(--accent)] transition-colors"
                placeholder="Teljes neved"
            >
            @error('name')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Email Address -->
        <div class="mb-4">
            <label for="email" class="block text-sm font-medium text-[var(--text-main)] mb-1">
                E-mail cím
            </label>
            <input
                id="email"
                type="email"
                name="email"
                value="{{ old('email') }}"
                required
                autocomplete="username"
                class="w-full px-4 py-3 rounded-[var(--radius-md)] border border-[var(--border-subtle)] bg-white text-[var(--text-main)] placeholder-[var(--text-muted)] focus:outline-none focus:border-[var(--accent)] focus:ring-1 focus:ring-[var(--accent)] transition-colors"
                placeholder="pelda@email.hu"
            >
            @error('email')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Password -->
        <div class="mb-4">
            <label for="password" class="block text-sm font-medium text-[var(--text-main)] mb-1">
                Jelszó
            </label>
            <input
                id="password"
                type="password"
                name="password"
                required
                autocomplete="new-password"
                class="w-full px-4 py-3 rounded-[var(--radius-md)] border border-[var(--border-subtle)] bg-white text-[var(--text-main)] placeholder-[var(--text-muted)] focus:outline-none focus:border-[var(--accent)] focus:ring-1 focus:ring-[var(--accent)] transition-colors"
                placeholder="Legalább 8 karakter"
            >
            @error('password')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Confirm Password -->
        <div class="mb-6">
            <label for="password_confirmation" class="block text-sm font-medium text-[var(--text-main)] mb-1">
                Jelszó megerősítése
            </label>
            <input
                id="password_confirmation"
                type="password"
                name="password_confirmation"
                required
                autocomplete="new-password"
                class="w-full px-4 py-3 rounded-[var(--radius-md)] border border-[var(--border-subtle)] bg-white text-[var(--text-main)] placeholder-[var(--text-muted)] focus:outline-none focus:border-[var(--accent)] focus:ring-1 focus:ring-[var(--accent)] transition-colors"
                placeholder="Jelszó újra"
            >
            @error('password_confirmation')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit" class="btn-primary w-full py-3">
            Regisztráció
        </button>

        <div class="mt-6 text-center">
            <span class="text-sm text-[var(--text-muted)]">Már van fiókod?</span>
            <a href="{{ route('login') }}" class="text-sm text-[var(--accent)] hover:text-[var(--accent-dark)] ml-1 font-medium transition-colors">
                Jelentkezz be!
            </a>
        </div>
    </form>
</x-layouts.shop-auth>
