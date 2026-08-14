<section>
    <header>
        <h2 class="text-lg font-semibold text-[var(--text-main)]">
            Kapcsolattartási adatok
        </h2>

        <p class="mt-1 text-sm text-[var(--text-muted)]">
            Add meg a telefonszámodat és jelezd, ha kivitelezőként vásárolsz.
        </p>
    </header>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-4">
        @csrf
        @method('patch')

        <div>
            <label for="phone" class="block text-sm font-medium text-[var(--text-main)] mb-1">
                Telefonszám
            </label>
            <input
                id="phone"
                name="phone"
                type="tel"
                value="{{ old('phone', $user->phone) }}"
                autocomplete="tel"
                placeholder="+36 30 123 4567"
                class="w-full px-4 py-3 rounded-[var(--radius-md)] border border-[var(--border-subtle)] bg-white text-[var(--text-main)] focus:outline-none focus:border-[var(--accent)] focus:ring-1 focus:ring-[var(--accent)] transition-colors"
            >
            @error('phone')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex items-start">
            <div class="flex items-center h-5">
                <input
                    id="is_contractor"
                    name="is_contractor"
                    type="checkbox"
                    value="1"
                    {{ old('is_contractor', $user->is_contractor) ? 'checked' : '' }}
                    class="w-4 h-4 text-[var(--accent)] border-[var(--border-subtle)] rounded focus:ring-[var(--accent)] focus:ring-offset-0"
                >
            </div>
            <div class="ml-3">
                <label for="is_contractor" class="text-sm font-medium text-[var(--text-main)]">
                    Kivitelezőként vásárolok
                </label>
                <p class="text-sm text-[var(--text-muted)]">
                    Jelöld be, ha kivitelezőként, nagyobb mennyiségben vásárolsz. Így kedvezményes árakhoz férhetsz hozzá.
                </p>
            </div>
        </div>
        @error('is_contractor')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror

        <div class="flex items-center gap-4 pt-2">
            <button type="submit" class="btn-primary py-2.5 px-6">
                Mentés
            </button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-[var(--accent)]"
                >Mentve!</p>
            @endif
        </div>
    </form>
</section>
