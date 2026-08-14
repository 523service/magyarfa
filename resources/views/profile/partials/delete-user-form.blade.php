<section x-data="{ confirmingDeletion: false }">
    <header>
        <h2 class="text-lg font-semibold text-[var(--text-main)]">
            Fiók törlése
        </h2>

        <p class="mt-1 text-sm text-[var(--text-muted)]">
            A fiók törlése után az összes adatod véglegesen törlésre kerül. A törlés előtt mentsd le az adataidat, amiket meg szeretnél tartani.
        </p>
    </header>

    <div class="mt-6">
        <button
            type="button"
            @click="confirmingDeletion = true"
            class="px-6 py-2.5 bg-red-600 text-white text-sm font-medium rounded-full hover:bg-red-700 transition-colors"
        >
            Fiók törlése
        </button>
    </div>

    <!-- Delete Confirmation Modal -->
    <div
        x-show="confirmingDeletion"
        x-cloak
        class="fixed inset-0 z-50 overflow-y-auto"
        aria-labelledby="modal-title"
        role="dialog"
        aria-modal="true"
    >
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <!-- Background overlay -->
            <div
                x-show="confirmingDeletion"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
                @click="confirmingDeletion = false"
            ></div>

            <!-- Modal panel -->
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div
                x-show="confirmingDeletion"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                class="inline-block align-bottom bg-white rounded-[var(--radius-lg)] text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full"
            >
                <form method="post" action="{{ route('profile.destroy') }}" class="p-6">
                    @csrf
                    @method('delete')

                    <h3 class="text-lg font-semibold text-[var(--text-main)]" id="modal-title">
                        Biztosan törölni szeretnéd a fiókodat?
                    </h3>

                    <p class="mt-2 text-sm text-[var(--text-muted)]">
                        A fiók törlése után az összes adatod véglegesen törlésre kerül. Kérjük, add meg a jelszavadat a törlés megerősítéséhez.
                    </p>

                    <div class="mt-4">
                        <label for="delete_password" class="sr-only">Jelszó</label>
                        <input
                            id="delete_password"
                            name="password"
                            type="password"
                            placeholder="Jelszó"
                            class="w-full px-4 py-3 rounded-[var(--radius-md)] border border-[var(--border-subtle)] bg-white text-[var(--text-main)] focus:outline-none focus:border-red-500 focus:ring-1 focus:ring-red-500 transition-colors"
                        >
                        @error('password', 'userDeletion')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mt-6 flex justify-end gap-3">
                        <button
                            type="button"
                            @click="confirmingDeletion = false"
                            class="px-6 py-2.5 bg-gray-100 text-[var(--text-main)] text-sm font-medium rounded-full hover:bg-gray-200 transition-colors"
                        >
                            Mégse
                        </button>

                        <button
                            type="submit"
                            class="px-6 py-2.5 bg-red-600 text-white text-sm font-medium rounded-full hover:bg-red-700 transition-colors"
                        >
                            Fiók végleges törlése
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

@if ($errors->userDeletion->isNotEmpty())
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.store('confirmingDeletion', true);
    });
</script>
@endif
