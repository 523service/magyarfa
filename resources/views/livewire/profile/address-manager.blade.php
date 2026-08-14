<div>
    <section>
        <header>
            <h2 class="text-lg font-semibold text-[var(--text-main)]">
                Címeim
            </h2>

            <p class="mt-1 text-sm text-[var(--text-muted)]">
                Kezeld a szállítási és számlázási címeidet.
            </p>
        </header>

        {{-- Tab Navigation --}}
        <div class="mt-6 border-b border-[var(--border-subtle)]">
            <nav class="-mb-px flex gap-6">
                <button
                    type="button"
                    wire:click="switchTab('shipping')"
                    class="py-3 px-1 border-b-2 font-medium text-sm transition-colors {{ $activeTab === 'shipping' ? 'border-[var(--accent)] text-[var(--accent)]' : 'border-transparent text-[var(--text-muted)] hover:text-[var(--text-main)] hover:border-gray-300' }}"
                >
                    Szállítási címek
                </button>
                <button
                    type="button"
                    wire:click="switchTab('billing')"
                    class="py-3 px-1 border-b-2 font-medium text-sm transition-colors {{ $activeTab === 'billing' ? 'border-[var(--accent)] text-[var(--accent)]' : 'border-transparent text-[var(--text-muted)] hover:text-[var(--text-main)] hover:border-gray-300' }}"
                >
                    Számlázási címek
                </button>
            </nav>
        </div>

        {{-- Address Cards --}}
        <div class="mt-6 space-y-4">
            @php
                $addresses = $activeTab === 'shipping' ? $this->shippingAddresses : $this->billingAddresses;
            @endphp

            @forelse($addresses as $address)
                <div
                    wire:key="address-{{ $address->id }}"
                    class="relative p-4 border border-[var(--border-subtle)] rounded-[var(--radius-md)] {{ $address->pivot->is_default ? 'ring-2 ring-[var(--accent)] ring-offset-1' : '' }}"
                >
                    {{-- Default Badge --}}
                    @if($address->pivot->is_default)
                        <span class="absolute top-2 right-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-[var(--accent)] text-white">
                            Alapértelmezett
                        </span>
                    @endif

                    {{-- Label --}}
                    @if($address->pivot->label)
                        <p class="text-sm font-semibold text-[var(--text-main)] mb-1">
                            {{ $address->pivot->label }}
                        </p>
                    @endif

                    {{-- Billing Name & Tax Number (for billing addresses) --}}
                    @if($activeTab === 'billing' && $address->pivot->billing_name)
                        <p class="font-medium text-[var(--text-main)]">
                            {{ $address->pivot->billing_name }}
                        </p>
                        @if($address->pivot->tax_number)
                            <p class="text-sm text-[var(--text-muted)]">
                                Adószám: {{ $address->pivot->tax_number }}
                            </p>
                        @endif
                    @endif

                    {{-- Address Details --}}
                    <p class="text-[var(--text-main)] {{ $activeTab === 'billing' && $address->pivot->billing_name ? 'mt-2' : '' }}">
                        {{ $address->street }}
                    </p>
                    <p class="text-[var(--text-main)]">
                        {{ $address->zip }} {{ $address->city }}
                    </p>
                    @if($address->state)
                        <p class="text-sm text-[var(--text-muted)]">
                            {{ $address->state }}
                        </p>
                    @endif
                    <p class="text-sm text-[var(--text-muted)]">
                        {{ $address->country }}
                    </p>

                    {{-- Actions --}}
                    <div class="mt-4 flex items-center gap-3">
                        <button
                            type="button"
                            wire:click="openModal({{ $address->id }})"
                            class="text-sm text-[var(--accent)] hover:text-[var(--accent-dark)] transition-colors"
                        >
                            Szerkesztés
                        </button>

                        @if(!$address->pivot->is_default)
                            <button
                                type="button"
                                wire:click="setDefault({{ $address->id }})"
                                class="text-sm text-[var(--text-muted)] hover:text-[var(--text-main)] transition-colors"
                            >
                                Alapértelmezetté tétel
                            </button>
                        @endif

                        <button
                            type="button"
                            wire:click="delete({{ $address->id }})"
                            wire:confirm="Biztosan törlöd ezt a címet?"
                            class="text-sm text-red-600 hover:text-red-700 transition-colors"
                        >
                            Törlés
                        </button>
                    </div>
                </div>
            @empty
                <div class="text-center py-8 text-[var(--text-muted)]">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-12 h-12 mx-auto mb-3 text-gray-300">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                    </svg>
                    <p>Még nincs {{ $activeTab === 'shipping' ? 'szállítási' : 'számlázási' }} címed.</p>
                </div>
            @endforelse

            {{-- Add New Address Button --}}
            <button
                type="button"
                wire:click="openModal"
                class="w-full py-3 px-4 border-2 border-dashed border-[var(--border-subtle)] rounded-[var(--radius-md)] text-[var(--text-muted)] hover:border-[var(--accent)] hover:text-[var(--accent)] transition-colors flex items-center justify-center gap-2"
            >
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5">
                    <path d="M10.75 4.75a.75.75 0 0 0-1.5 0v4.5h-4.5a.75.75 0 0 0 0 1.5h4.5v4.5a.75.75 0 0 0 1.5 0v-4.5h4.5a.75.75 0 0 0 0-1.5h-4.5v-4.5Z" />
                </svg>
                Új {{ $activeTab === 'shipping' ? 'szállítási' : 'számlázási' }} cím hozzáadása
            </button>
        </div>
    </section>

    {{-- Modal --}}
    @if($showModal)
        {{-- Backdrop --}}
        <div
            class="fixed inset-0 bg-black/50 z-40 transition-opacity"
            wire:click="closeModal"
            x-data
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
        ></div>

        {{-- Modal Content --}}
        <div
            class="fixed inset-0 z-50 flex items-center justify-center p-4"
            x-data
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
        >
            <div class="bg-white rounded-[var(--radius-lg)] shadow-xl max-w-md w-full max-h-[90vh] overflow-y-auto">
                {{-- Header --}}
                <div class="flex items-center justify-between p-4 border-b border-[var(--border-subtle)]">
                    <h3 class="text-lg font-semibold text-[var(--text-main)]">
                        {{ $editingAddressId ? 'Cím szerkesztése' : 'Új ' . ($activeTab === 'shipping' ? 'szállítási' : 'számlázási') . ' cím' }}
                    </h3>
                    <button
                        type="button"
                        wire:click="closeModal"
                        class="text-gray-400 hover:text-gray-600 transition-colors"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5">
                            <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z" />
                        </svg>
                    </button>
                </div>

                {{-- Form --}}
                <form wire:submit="save" class="p-4 space-y-4">
                    {{-- Label --}}
                    <div>
                        <label for="label" class="block text-sm font-medium text-[var(--text-main)] mb-1">
                            Címke <span class="text-[var(--text-muted)]">(opcionális)</span>
                        </label>
                        <input
                            id="label"
                            type="text"
                            wire:model="label"
                            placeholder="pl. Otthon, Iroda"
                            class="w-full px-4 py-3 rounded-[var(--radius-md)] border border-[var(--border-subtle)] bg-white text-[var(--text-main)] focus:outline-none focus:border-[var(--accent)] focus:ring-1 focus:ring-[var(--accent)] transition-colors"
                        >
                        @error('label')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Billing Name (only for billing) --}}
                    @if($activeTab === 'billing')
                        <div>
                            <label for="billingName" class="block text-sm font-medium text-[var(--text-main)] mb-1">
                                Számlázási név <span class="text-red-500">*</span>
                            </label>
                            <input
                                id="billingName"
                                type="text"
                                wire:model="billingName"
                                required
                                placeholder="Cégnév vagy magánszemély neve"
                                class="w-full px-4 py-3 rounded-[var(--radius-md)] border border-[var(--border-subtle)] bg-white text-[var(--text-main)] focus:outline-none focus:border-[var(--accent)] focus:ring-1 focus:ring-[var(--accent)] transition-colors"
                            >
                            @error('billingName')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="taxNumber" class="block text-sm font-medium text-[var(--text-main)] mb-1">
                                Adószám <span class="text-[var(--text-muted)]">(opcionális)</span>
                            </label>
                            <input
                                id="taxNumber"
                                type="text"
                                wire:model="taxNumber"
                                placeholder="12345678-1-23"
                                class="w-full px-4 py-3 rounded-[var(--radius-md)] border border-[var(--border-subtle)] bg-white text-[var(--text-main)] focus:outline-none focus:border-[var(--accent)] focus:ring-1 focus:ring-[var(--accent)] transition-colors"
                            >
                            @error('taxNumber')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    @endif

                    {{-- Street --}}
                    <div>
                        <label for="street" class="block text-sm font-medium text-[var(--text-main)] mb-1">
                            Utca, házszám <span class="text-red-500">*</span>
                        </label>
                        <input
                            id="street"
                            type="text"
                            wire:model="street"
                            required
                            placeholder="Kossuth utca 10."
                            class="w-full px-4 py-3 rounded-[var(--radius-md)] border border-[var(--border-subtle)] bg-white text-[var(--text-main)] focus:outline-none focus:border-[var(--accent)] focus:ring-1 focus:ring-[var(--accent)] transition-colors"
                        >
                        @error('street')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Zip & City --}}
                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <label for="zip" class="block text-sm font-medium text-[var(--text-main)] mb-1">
                                Irányítószám <span class="text-red-500">*</span>
                            </label>
                            <input
                                id="zip"
                                type="text"
                                wire:model="zip"
                                required
                                placeholder="1234"
                                class="w-full px-4 py-3 rounded-[var(--radius-md)] border border-[var(--border-subtle)] bg-white text-[var(--text-main)] focus:outline-none focus:border-[var(--accent)] focus:ring-1 focus:ring-[var(--accent)] transition-colors"
                            >
                            @error('zip')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="col-span-2">
                            <label for="city" class="block text-sm font-medium text-[var(--text-main)] mb-1">
                                Város <span class="text-red-500">*</span>
                            </label>
                            <input
                                id="city"
                                type="text"
                                wire:model="city"
                                required
                                placeholder="Budapest"
                                class="w-full px-4 py-3 rounded-[var(--radius-md)] border border-[var(--border-subtle)] bg-white text-[var(--text-main)] focus:outline-none focus:border-[var(--accent)] focus:ring-1 focus:ring-[var(--accent)] transition-colors"
                            >
                            @error('city')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    {{-- State --}}
                    {{--
                    <div>
                        <label for="state" class="block text-sm font-medium text-[var(--text-main)] mb-1">
                            Megye <span class="text-[var(--text-muted)]">(opcionális)</span>
                        </label>
                        <input
                            id="state"
                            type="text"
                            wire:model="state"
                            placeholder="Pest megye"
                            class="w-full px-4 py-3 rounded-[var(--radius-md)] border border-[var(--border-subtle)] bg-white text-[var(--text-main)] focus:outline-none focus:border-[var(--accent)] focus:ring-1 focus:ring-[var(--accent)] transition-colors"
                        >
                        @error('state')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    --}}

                    {{-- Country --}}
                    <div>
                        <label for="country" class="block text-sm font-medium text-[var(--text-main)] mb-1">
                            Ország <span class="text-red-500">*</span>
                        </label>
                        <select
                            id="country"
                            wire:model="country"
                            required
                            class="w-full px-4 py-3 rounded-[var(--radius-md)] border border-[var(--border-subtle)] bg-white text-[var(--text-main)] focus:outline-none focus:border-[var(--accent)] focus:ring-1 focus:ring-[var(--accent)] transition-colors"
                        >
                            <option value="HU">Magyarország</option>
                            <option value="AT">Ausztria</option>
                            <option value="SK">Szlovákia</option>
                            <option value="RO">Románia</option>
                            <option value="RS">Szerbia</option>
                            <option value="HR">Horvátország</option>
                            <option value="SI">Szlovénia</option>
                            <option value="UA">Ukrajna</option>
                            <option value="DE">Németország</option>
                        </select>
                        @error('country')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Default Checkbox --}}
                    <div class="flex items-center">
                        <input
                            id="isDefault"
                            type="checkbox"
                            wire:model="isDefault"
                            class="w-4 h-4 text-[var(--accent)] border-[var(--border-subtle)] rounded focus:ring-[var(--accent)] focus:ring-offset-0"
                        >
                        <label for="isDefault" class="ml-2 text-sm text-[var(--text-main)]">
                            Alapértelmezett cím
                        </label>
                    </div>

                    {{-- Actions --}}
                    <div class="flex gap-3 pt-4 border-t border-[var(--border-subtle)]">
                        <button
                            type="button"
                            wire:click="closeModal"
                            class="flex-1 py-2.5 px-4 border border-[var(--border-subtle)] rounded-[var(--radius-md)] text-[var(--text-main)] font-medium hover:bg-gray-50 transition-colors"
                        >
                            Mégse
                        </button>
                        <button
                            type="submit"
                            class="flex-1 btn-primary py-2.5 px-4"
                        >
                            {{ $editingAddressId ? 'Mentés' : 'Hozzáadás' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
