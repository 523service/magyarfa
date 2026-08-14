<x-layouts.shop-auth>
    <x-slot name="title">Bejelentkezés - MagyarSzigetelés.hu</x-slot>

    <div class="text-center mb-6">
        <h1 class="text-2xl font-bold text-[var(--text-main)]">Bejelentkezés</h1>
        <p class="text-sm text-[var(--text-muted)] mt-2">Jelentkezz be a fiókodba</p>
    </div>

    <!-- Session Status -->
    @if (session('status'))
        <div class="mb-4 p-3 rounded-[var(--radius-md)] bg-[var(--accent-soft)] text-[var(--accent-dark)] text-sm">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}">
        @csrf

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
                autofocus
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
                autocomplete="current-password"
                class="w-full px-4 py-3 rounded-[var(--radius-md)] border border-[var(--border-subtle)] bg-white text-[var(--text-main)] placeholder-[var(--text-muted)] focus:outline-none focus:border-[var(--accent)] focus:ring-1 focus:ring-[var(--accent)] transition-colors"
                placeholder="********"
            >
            @error('password')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Remember Me -->
        <div class="flex items-center justify-between mb-6">
            <label for="remember_me" class="flex items-center">
                <input
                    id="remember_me"
                    type="checkbox"
                    name="remember"
                    class="w-4 h-4 rounded border-[var(--border-subtle)] text-[var(--accent)] focus:ring-[var(--accent)]"
                >
                <span class="ml-2 text-sm text-[var(--text-muted)]">Emlékezz rám</span>
            </label>

            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" class="text-sm text-[var(--accent)] hover:text-[var(--accent-dark)] transition-colors">
                    Elfelejtett jelszó?
                </a>
            @endif
        </div>

        <button type="submit" class="btn-primary w-full py-3">
            Bejelentkezés
        </button>

        <div class="mt-6 text-center">
            <span class="text-sm text-[var(--text-muted)]">Még nincs fiókod?</span>
            <a href="{{ route('register') }}" class="text-sm text-[var(--accent)] hover:text-[var(--accent-dark)] ml-1 font-medium transition-colors">
                Regisztrálj!
            </a>
        </div>
    </form>
</x-layouts.shop-auth>
