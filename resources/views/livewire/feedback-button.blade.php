<div
    x-data
    x-init="
        $wire.currentUrl = window.location.href;
        $wire.screenWidth = screen.width;
        $wire.screenHeight = screen.height;
    "
>
    {{-- Floating trigger button --}}
    <button
        type="button"
        wire:click="openModal"
        title="Hibabejelentés"
        class="fixed bottom-6 right-6 z-30 flex items-center gap-1.5 rounded-full bg-[var(--warning)] px-3 py-2 text-white shadow-lg transition-all hover:bg-[var(--warning-hover)] hover:shadow-xl focus:outline-none focus:ring-2 focus:ring-[var(--warnimg)] focus:ring-offset-2"
    >
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4 shrink-0">
            <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z" />
            <path fill-rule="evenodd" d="M10 3a7 7 0 1 0 0 14A7 7 0 0 0 10 3ZM1 10a9 9 0 1 1 18 0 9 9 0 0 1-18 0Z" clip-rule="evenodd" />
        </svg>
        <span class="hidden text-xs font-medium sm:inline">Hibát talált?</span>
    </button>

    {{-- Modal --}}
    @if($showModal)
        {{-- Backdrop --}}
        <div
            class="fixed inset-0 z-[60] bg-black/50 transition-opacity"
            wire:click="closeModal"
            x-data
            x-transition:enter="ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
        ></div>

        {{-- Modal panel --}}
        <div
            class="fixed inset-0 z-[61] flex items-end justify-center p-0 sm:items-center sm:p-4"
            x-data
            x-transition:enter="ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave="ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        >
            <div class="w-full rounded-t-2xl bg-white shadow-2xl sm:max-w-md sm:rounded-2xl">
                {{-- Header --}}
                <div class="flex items-center justify-between border-b border-[var(--border-subtle)] px-5 py-4">
                    <div class="flex items-center gap-2 text-[var(--text-main)]">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5 text-[var(--accent)]">
                            <path fill-rule="evenodd" d="M10 3a7 7 0 1 0 0 14A7 7 0 0 0 10 3ZM1 10a9 9 0 1 1 18 0 9 9 0 0 1-18 0Z" clip-rule="evenodd" />
                            <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z" />
                        </svg>
                        <span class="font-semibold">Hibabejelentés</span>
                    </div>
                    <button
                        type="button"
                        wire:click="closeModal"
                        class="rounded-md p-1 text-[var(--text-muted)] transition-colors hover:bg-gray-100 hover:text-[var(--text-main)]"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5">
                            <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z" />
                        </svg>
                    </button>
                </div>

                {{-- Body --}}
                <div class="px-5 py-4">
                    @if($submitted)
                        {{-- Success state --}}
                        <div class="flex flex-col items-center gap-3 py-6 text-center">
                            <div class="flex h-14 w-14 items-center justify-center rounded-full bg-[var(--accent-soft)]">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-7 w-7 text-[var(--accent)]">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.857-9.809a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div>
                                <p class="font-semibold text-[var(--text-main)]">Köszönjük a bejelentést!</p>
                                <p class="mt-1 text-sm text-[var(--text-muted)]">Hamarosan megvizsgáljuk és javítjuk.</p>
                            </div>
                            <button
                                type="button"
                                wire:click="closeModal"
                                class="mt-2 rounded-lg bg-[var(--accent)] px-5 py-2 text-sm font-medium text-white transition-colors hover:bg-[var(--accent-hover)]"
                            >
                                Bezárás
                            </button>
                        </div>
                    @else
                        {{-- Form --}}
                        <form wire:submit="submit" class="flex flex-col gap-4">
                            <div class="grid gap-4 sm:grid-cols-2">
                                {{-- Név --}}
                                <div>
                                    <label for="fb-name" class="mb-1 block text-sm font-medium text-[var(--text-main)]">
                                        Név <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        id="fb-name"
                                        type="text"
                                        wire:model="name"
                                        autocomplete="name"
                                        placeholder=""
                                        class="w-full rounded-lg border border-[var(--border-subtle)] bg-white px-3 py-2 text-sm text-[var(--text-main)] placeholder-[var(--text-muted)] transition focus:border-[var(--accent)] focus:outline-none focus:ring-1 focus:ring-[var(--accent)] @error('name') border-red-400 @enderror"
                                    >
                                    @error('name')
                                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                    @enderror
                                </div>

                                {{-- Email --}}
                                <div>
                                    <label for="fb-email" class="mb-1 block text-sm font-medium text-[var(--text-main)]">
                                        Email
                                    </label>
                                    <input
                                        id="fb-email"
                                        type="email"
                                        wire:model="email"
                                        autocomplete="email"
                                        placeholder=""
                                        class="w-full rounded-lg border border-[var(--border-subtle)] bg-white px-3 py-2 text-sm text-[var(--text-main)] placeholder-[var(--text-muted)] transition focus:border-[var(--accent)] focus:outline-none focus:ring-1 focus:ring-[var(--accent)] @error('email') border-red-400 @enderror"
                                    >
                                    @error('email')
                                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            {{-- Leírás --}}
                            <div>
                                <label for="fb-description" class="mb-1 block text-sm font-medium text-[var(--text-main)]">
                                    Hiba leírása <span class="text-red-500">*</span>
                                </label>
                                <textarea
                                    id="fb-description"
                                    wire:model="description"
                                    rows="4"
                                    placeholder="Írja le részletesen, mit tapasztalt..."
                                    class="w-full resize-none rounded-lg border border-[var(--border-subtle)] bg-white px-3 py-2 text-sm text-[var(--text-main)] placeholder-[var(--text-muted)] transition focus:border-[var(--accent)] focus:outline-none focus:ring-1 focus:ring-[var(--accent)] @error('description') border-red-400 @enderror"
                                ></textarea>
                                @error('description')
                                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Screenshot placeholder --}}
                            <div>
                                <button
                                    type="button"
                                    disabled
                                    class="flex w-full cursor-not-allowed items-center justify-center gap-2 rounded-lg border border-dashed border-[var(--border-subtle)] px-4 py-2.5 text-sm text-[var(--text-muted)] opacity-60"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4">
                                        <path fill-rule="evenodd" d="M1 5.25A2.25 2.25 0 0 1 3.25 3h13.5A2.25 2.25 0 0 1 19 5.25v9.5A2.25 2.25 0 0 1 16.75 17H3.25A2.25 2.25 0 0 1 1 14.75v-9.5Zm1.5 5.81v3.69c0 .414.336.75.75.75h13.5a.75.75 0 0 0 .75-.75v-2.69l-2.22-2.219a.75.75 0 0 0-1.06 0l-1.91 1.909.47.47a.75.75 0 1 1-1.06 1.06L6.53 8.091a.75.75 0 0 0-1.06 0l-2.97 2.97ZM12 7a1 1 0 1 1-2 0 1 1 0 0 1 2 0Z" clip-rule="evenodd" />
                                    </svg>
                                    Képernyőkép csatolása
                                    <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs text-[var(--text-muted)]">Hamarosan</span>
                                </button>
                            </div>

                            {{-- Actions --}}
                            <div class="flex items-center justify-end gap-3 border-t border-[var(--border-subtle)] pt-4">
                                <button
                                    type="button"
                                    wire:click="closeModal"
                                    class="rounded-lg px-4 py-2 text-sm font-medium text-[var(--text-muted)] transition-colors hover:bg-gray-100 hover:text-[var(--text-main)]"
                                >
                                    Mégsem
                                </button>
                                <button
                                    type="submit"
                                    wire:loading.attr="disabled"
                                    class="flex items-center gap-2 rounded-lg bg-[var(--accent)] px-5 py-2 text-sm font-medium text-white transition-colors hover:bg-[var(--accent-hover)] disabled:opacity-60"
                                >
                                    <span wire:loading.remove wire:target="submit">Bejelentés küldése</span>
                                    <span wire:loading wire:target="submit">Küldés...</span>
                                </button>
                            </div>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>
