<div class="py-6 lg:py-10">
    {{-- Breadcrumb --}}
    <nav class="product-breadcrumb mb-6">
        <a href="{{ route('home') }}">Főoldal</a>
        <span class="breadcrumb-separator">></span>
        <a href="{{ route('cart.index') }}">Kosár</a>
        <span class="breadcrumb-separator">></span>
        <span class="breadcrumb-current">Rendelés</span>
    </nav>

    <h1 class="text-2xl lg:text-3xl font-bold text-gray-900 mb-6">Rendelés</h1>

    <x-shop.price-notice class="mb-6" />

    {{-- Progress Steps --}}
    <div class="flex items-center gap-2 mb-8">
        @foreach (['auth' => 'Azonosítás', 'address' => 'Cím', 'shipping' => 'Szállítás', 'payment' => 'Fizetés'] as $stepKey => $stepLabel)
            @if (! Auth::check() || $stepKey !== 'auth')
                <div class="flex items-center gap-2">
                    @if (!$loop->first && (Auth::check() || $stepKey !== 'auth'))
                        <div class="w-8 h-0.5 bg-gray-200"></div>
                    @endif
                    <div class="flex items-center gap-1.5">
                        <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-semibold
                            @if ($step === $stepKey) bg-[var(--accent)] text-white
                            @elseif (array_search($step, array_keys(['auth' => 1, 'address' => 2, 'shipping' => 3, 'payment' => 4])) > array_search($stepKey, array_keys(['auth' => 1, 'address' => 2, 'shipping' => 3, 'payment' => 4]))) bg-[var(--accent)] text-white
                            @else bg-gray-200 text-gray-500
                            @endif
                        ">
                            {{ $loop->iteration }}
                        </div>
                        <span class="text-sm font-medium
                            @if ($step === $stepKey) text-[var(--accent)]
                            @else text-gray-500
                            @endif
                        ">{{ $stepLabel }}</span>
                    </div>
                </div>
            @endif
        @endforeach
    </div>

    <div class="lg:grid lg:grid-cols-3 lg:gap-8">
        {{-- LEFT: Step content --}}
        <div class="lg:col-span-2 space-y-6">

            {{-- ============================================================
                 STEP 1: AUTH
                 ============================================================ --}}
            <div wire:show="step === 'auth'">
                <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                    {{-- Tab nav --}}
                    <div class="flex border-b border-gray-200">
                        <button type="button" wire:click="$set('authTab', 'guest')"
                            class="flex-1 px-4 py-3 text-sm font-medium text-center transition-colors
                                @if ($authTab === 'guest') border-b-2 border-[var(--accent)] text-[var(--accent)] @else text-gray-500 hover:text-gray-700 @endif">
                            Vendégként tovább
                        </button>
                        <button type="button" wire:click="$set('authTab', 'login')"
                            class="flex-1 px-4 py-3 text-sm font-medium text-center transition-colors
                                @if ($authTab === 'login') border-b-2 border-[var(--accent)] text-[var(--accent)] @else text-gray-500 hover:text-gray-700 @endif">
                            Bejelentkezés
                        </button>
                        <button type="button" wire:click="$set('authTab', 'register')"
                            class="flex-1 px-4 py-3 text-sm font-medium text-center transition-colors
                                @if ($authTab === 'register') border-b-2 border-[var(--accent)] text-[var(--accent)] @else text-gray-500 hover:text-gray-700 @endif">
                            Regisztrálás
                        </button>
                    </div>

                    <div class="p-6">
                        {{-- Guest tab --}}
                        <div wire:show="authTab === 'guest'" class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Teljes név</label>
                                <input type="text" wire:model="guestName"
                                    class="w-full px-4 py-3 rounded-lg border border-[var(--border-subtle)] bg-white text-[var(--text-main)] placeholder-[var(--text-muted)] focus:outline-none focus:border-[var(--accent)] focus:ring-1 focus:ring-[var(--accent)] transition-colors"
                                    placeholder="Pl. Kovács János">
                                @error('guestName') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">E-mail cím</label>
                                <input type="email" wire:model="guestEmail"
                                    class="w-full px-4 py-3 rounded-lg border border-[var(--border-subtle)] bg-white text-[var(--text-main)] placeholder-[var(--text-muted)] focus:outline-none focus:border-[var(--accent)] focus:ring-1 focus:ring-[var(--accent)] transition-colors"
                                    placeholder="pl. kovacs@email.com">
                                @error('guestEmail') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <button type="button" wire:click="continueAsGuest" wire:loading.attr="disabled"
                                class="w-full px-6 py-3 bg-[var(--accent)] text-white rounded-lg font-semibold hover:bg-[var(--accent-hover)] transition-colors disabled:opacity-60">
                                <span wire:loading.remove>Tovább</span>
                                <span wire:loading>Feldolgozás...</span>
                            </button>
                        </div>

                        {{-- Login tab --}}
                        <div wire:show="authTab === 'login'" class="space-y-4">
                            @if ($authError)
                                <div class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg px-4 py-3">{{ $authError }}</div>
                            @endif
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">E-mail cím</label>
                                <input type="email" wire:model="loginEmail"
                                    class="w-full px-4 py-3 rounded-lg border border-[var(--border-subtle)] bg-white text-[var(--text-main)] placeholder-[var(--text-muted)] focus:outline-none focus:border-[var(--accent)] focus:ring-1 focus:ring-[var(--accent)] transition-colors"
                                    placeholder="pl. kovacs@email.com">
                                @error('loginEmail') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Jelszó</label>
                                <input type="password" wire:model="loginPassword"
                                    class="w-full px-4 py-3 rounded-lg border border-[var(--border-subtle)] bg-white text-[var(--text-main)] placeholder-[var(--text-muted)] focus:outline-none focus:border-[var(--accent)] focus:ring-1 focus:ring-[var(--accent)] transition-colors"
                                    placeholder="Jelszó">
                                @error('loginPassword') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <button type="button" wire:click="loginAndContinue" wire:loading.attr="disabled"
                                class="w-full px-6 py-3 bg-[var(--accent)] text-white rounded-lg font-semibold hover:bg-[var(--accent-hover)] transition-colors disabled:opacity-60">
                                <span wire:loading.remove>Bejelentkezés</span>
                                <span wire:loading>Feldolgozás...</span>
                            </button>
                        </div>

                        {{-- Register tab --}}
                        <div wire:show="authTab === 'register'" class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Teljes név</label>
                                <input type="text" wire:model="registerName"
                                    class="w-full px-4 py-3 rounded-lg border border-[var(--border-subtle)] bg-white text-[var(--text-main)] placeholder-[var(--text-muted)] focus:outline-none focus:border-[var(--accent)] focus:ring-1 focus:ring-[var(--accent)] transition-colors"
                                    placeholder="Pl. Kovács János">
                                @error('registerName') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">E-mail cím</label>
                                <input type="email" wire:model="registerEmail"
                                    class="w-full px-4 py-3 rounded-lg border border-[var(--border-subtle)] bg-white text-[var(--text-main)] placeholder-[var(--text-muted)] focus:outline-none focus:border-[var(--accent)] focus:ring-1 focus:ring-[var(--accent)] transition-colors"
                                    placeholder="pl. kovacs@email.com">
                                @error('registerEmail') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Jelszó</label>
                                <input type="password" wire:model="registerPassword"
                                    class="w-full px-4 py-3 rounded-lg border border-[var(--border-subtle)] bg-white text-[var(--text-main)] placeholder-[var(--text-muted)] focus:outline-none focus:border-[var(--accent)] focus:ring-1 focus:ring-[var(--accent)] transition-colors"
                                    placeholder="Min. 8 karakter">
                                @error('registerPassword') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Jelszó megerősítése</label>
                                <input type="password" wire:model="registerPasswordConfirmation"
                                    class="w-full px-4 py-3 rounded-lg border border-[var(--border-subtle)] bg-white text-[var(--text-main)] placeholder-[var(--text-muted)] focus:outline-none focus:border-[var(--accent)] focus:ring-1 focus:ring-[var(--accent)] transition-colors"
                                    placeholder="Ismételjük meg">
                            </div>
                            <button type="button" wire:click="registerAndContinue" wire:loading.attr="disabled"
                                class="w-full px-6 py-3 bg-[var(--accent)] text-white rounded-lg font-semibold hover:bg-[var(--accent-hover)] transition-colors disabled:opacity-60">
                                <span wire:loading.remove>Regisztrálás és tovább</span>
                                <span wire:loading>Feldolgozás...</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ============================================================
                 STEP 2: ADDRESS
                 ============================================================ --}}
            <div wire:show="step === 'address'">
                <div class="bg-white rounded-lg shadow-sm p-6 space-y-6">
                    {{-- Shipping address --}}
                    <div>
                        <h3 class="text-base font-semibold text-gray-900 mb-3">Szállítási cím</h3>

                        {{-- Saved addresses (logged-in only) --}}
                        @if (Auth::check() && $shippingAddresses->isNotEmpty())
                            <div class="grid sm:grid-cols-2 gap-3 mb-3">
                                @foreach ($shippingAddresses as $address)
                                    <button type="button" wire:click="selectShippingAddress({{ $address->id }})"
                                        class="text-left p-4 rounded-lg border transition-all
                                            @if ($selectedShippingAddressId === $address->id) ring-2 ring-[var(--accent)] ring-offset-1 border-[var(--accent)] @else border-gray-200 hover:border-gray-300 @endif">
                                        @if ($address->pivot->label)
                                            <p class="text-sm font-semibold text-gray-900 mb-0.5">{{ $address->pivot->label }}</p>
                                        @endif
                                        <p class="text-sm text-gray-700">{{ $address->street }}</p>
                                        <p class="text-sm text-gray-500">{{ $address->zip }} {{ $address->city }}</p>
                                    </button>
                                @endforeach
                            </div>

                            <button type="button" wire:click="addNewShippingAddress"
                                class="text-sm text-[var(--accent)] hover:text-[var(--accent-hover)] font-medium mb-3">
                                + Új szállítási cím
                            </button>
                        @endif

                        {{-- Inline address form --}}
                        <div wire:show="$wire.addingNewShippingAddress || {{ $isGuest ? 'true' : 'false' }}" class="space-y-3">
                            <div class="grid sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-sm font-medium text-gray-600 mb-1">Ország</label>
                                    <select wire:model="shippingCountry"
                                            class="w-full px-4 py-2.5 rounded-lg border border-[var(--border-subtle)] bg-white text-[var(--text-main)] focus:outline-none focus:border-[var(--accent)] focus:ring-1 focus:ring-[var(--accent)]">
                                        <option value="HU">Magyarország</option>
                                        <option value="AT">Austria</option>
                                        <option value="DE">Germany</option>
                                        <option value="SK">Slovakia</option>
                                        <option value="RO">Romania</option>
                                        <option value="CZ">Czech Republic</option>
                                        <option value="PL">Poland</option>
                                    </select>
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="block text-sm font-medium text-gray-600 mb-1">Utca, házszám</label>
                                    <input type="text" wire:model="shippingStreet"
                                        class="w-full px-4 py-2.5 rounded-lg border border-[var(--border-subtle)] bg-white text-[var(--text-main)] focus:outline-none focus:border-[var(--accent)] focus:ring-1 focus:ring-[var(--accent)]"
                                        placeholder="Pl. Kossuth utca 10.">
                                    @error('shippingStreet') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-600 mb-1">Irányítószám</label>
                                    <input type="text" wire:model="shippingZip" maxlength="5"
                                        class="w-full px-4 py-2.5 rounded-lg border border-[var(--border-subtle)] bg-white text-[var(--text-main)] focus:outline-none focus:border-[var(--accent)] focus:ring-1 focus:ring-[var(--accent)]"
                                        placeholder="1234">
                                    @error('shippingZip') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-600 mb-1">Település</label>
                                    <input type="text" wire:model="shippingCity"
                                        class="w-full px-4 py-2.5 rounded-lg border border-[var(--border-subtle)] bg-white text-[var(--text-main)] focus:outline-none focus:border-[var(--accent)] focus:ring-1 focus:ring-[var(--accent)]"
                                        placeholder="Budapest">
                                    @error('shippingCity') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-600 mb-1">Megye (opcionális)</label>
                                    <input type="text" wire:model="shippingState"
                                        class="w-full px-4 py-2.5 rounded-lg border border-[var(--border-subtle)] bg-white text-[var(--text-main)] focus:outline-none focus:border-[var(--accent)] focus:ring-1 focus:ring-[var(--accent)]">
                                </div>

                            </div>
                        </div>
                    </div>

                    <hr class="border-gray-200">

                    {{-- Billing address --}}
                    <div>
                        <div class="flex items-center gap-3 mb-3">
                            <h3 class="text-base font-semibold text-gray-900">Számlázási cím</h3>
                            <label class="flex items-center gap-2 ml-auto">
                                <input type="checkbox" wire:model="billingIsSameAsShipping"
                                    class="w-4 h-4 rounded border-gray-300 text-[var(--accent)] focus:ring-[var(--accent)]">
                                <span class="text-sm text-gray-600">Megegyezik a szállítási címmel</span>
                            </label>
                        </div>

                        <div wire:show="!billingIsSameAsShipping">
                            {{-- Saved billing addresses (logged-in only) --}}
                            @if (Auth::check() && $billingAddresses->isNotEmpty())
                                <div class="grid sm:grid-cols-2 gap-3 mb-3">
                                    @foreach ($billingAddresses as $address)
                                        <button type="button" wire:click="selectBillingAddress({{ $address->id }})"
                                            class="text-left p-4 rounded-lg border transition-all
                                                @if ($selectedBillingAddressId === $address->id) ring-2 ring-[var(--accent)] ring-offset-1 border-[var(--accent)] @else border-gray-200 hover:border-gray-300 @endif">
                                            @if ($address->pivot->label)
                                                <p class="text-sm font-semibold text-gray-900 mb-0.5">{{ $address->pivot->label }}</p>
                                            @endif
                                            <p class="text-sm text-gray-700">{{ $address->street }}</p>
                                            <p class="text-sm text-gray-500">{{ $address->zip }} {{ $address->city }}</p>
                                        </button>
                                    @endforeach
                                </div>

                                <button type="button" wire:click="addNewBillingAddress"
                                    class="text-sm text-[var(--accent)] hover:text-[var(--accent-hover)] font-medium mb-3">
                                    + Új számlázási cím
                                </button>
                            @endif

                            {{-- Inline billing form --}}
                            <div wire:show="$wire.addingNewBillingAddress || {{ $isGuest ? 'true' : 'false' }}" class="space-y-3">
                                <div class="grid sm:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-600 mb-1">Számlázási névre</label>
                                        <input type="text" wire:model="billingName"
                                            class="w-full px-4 py-2.5 rounded-lg border border-[var(--border-subtle)] bg-white text-[var(--text-main)] focus:outline-none focus:border-[var(--accent)] focus:ring-1 focus:ring-[var(--accent)]"
                                            placeholder="Pl. Kovács János">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-600 mb-1">Adószám (nem kötelező)</label>
                                        <input type="text" wire:model="billingTaxNumber"
                                            class="w-full px-4 py-2.5 rounded-lg border border-[var(--border-subtle)] bg-white text-[var(--text-main)] focus:outline-none focus:border-[var(--accent)] focus:ring-1 focus:ring-[var(--accent)]"
                                            placeholder="HU12345678">
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label class="block text-sm font-medium text-gray-600 mb-1">Utca, házszám</label>
                                        <input type="text" wire:model="billingStreet"
                                            class="w-full px-4 py-2.5 rounded-lg border border-[var(--border-subtle)] bg-white text-[var(--text-main)] focus:outline-none focus:border-[var(--accent)] focus:ring-1 focus:ring-[var(--accent)]"
                                            placeholder="Pl. Kossuth utca 10.">
                                        @error('billingStreet') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-600 mb-1">Irányítószám</label>
                                        <input type="text" wire:model="billingZip" maxlength="5"
                                            class="w-full px-4 py-2.5 rounded-lg border border-[var(--border-subtle)] bg-white text-[var(--text-main)] focus:outline-none focus:border-[var(--accent)] focus:ring-1 focus:ring-[var(--accent)]"
                                            placeholder="1234">
                                        @error('billingZip') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-600 mb-1">Város</label>
                                        <input type="text" wire:model="billingCity"
                                            class="w-full px-4 py-2.5 rounded-lg border border-[var(--border-subtle)] bg-white text-[var(--text-main)] focus:outline-none focus:border-[var(--accent)] focus:ring-1 focus:ring-[var(--accent)]"
                                            placeholder="Budapest">
                                        @error('billingCity') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-600 mb-1">Megye (nem kötelező)</label>
                                        <input type="text" wire:model="billingState"
                                            class="w-full px-4 py-2.5 rounded-lg border border-[var(--border-subtle)] bg-white text-[var(--text-main)] focus:outline-none focus:border-[var(--accent)] focus:ring-1 focus:ring-[var(--accent)]">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-600 mb-1">Ország</label>
                                        <select wire:model="billingCountry"
                                            class="w-full px-4 py-2.5 rounded-lg border border-[var(--border-subtle)] bg-white text-[var(--text-main)] focus:outline-none focus:border-[var(--accent)] focus:ring-1 focus:ring-[var(--accent)]">
                                            <option value="HU">Magyarország</option>
                                            <option value="AT">Austria</option>
                                            <option value="DE">Germany</option>
                                            <option value="SK">Slovakia</option>
                                            <option value="RO">Romania</option>
                                            <option value="CZ">Czech Republic</option>
                                            <option value="PL">Poland</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Navigation --}}
                    <div class="flex items-center justify-between pt-2">
                        @if (!Auth::check())
                            <button type="button" wire:click="back('auth')"
                                class="text-sm text-gray-600 hover:text-gray-800 font-medium">
                                ← Vissza
                            </button>
                        @endif
                        <button type="button" wire:click="continueToShipping" wire:loading.attr="disabled"
                            class="ml-auto px-6 py-2.5 bg-[var(--accent)] text-white rounded-lg font-semibold hover:bg-[var(--accent-hover)] transition-colors disabled:opacity-60">
                            <span wire:loading.remove>Tovább</span>
                            <span wire:loading>Feldolgozás...</span>
                        </button>
                    </div>
                </div>
            </div>

            {{-- ============================================================
                 STEP 3: SHIPPING
                 ============================================================ --}}
            <div wire:show="step === 'shipping'">
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h3 class="text-base font-semibold text-gray-900 mb-4">Szállítási mód</h3>

                    <div class="grid sm:grid-cols-2 gap-3 mb-4">
                        {{-- Courier --}}
                        <label class="block cursor-pointer">
                            <input type="radio" wire:model.live="shippingMethod" value="courier" class="sr-only">
                            <div class="p-4 rounded-lg border-2 transition-all
                                @if ($shippingMethod === 'courier') border-[var(--accent)] bg-[var(--accent)]/5 @else border-gray-200 hover:border-gray-300 @endif">
                                <div class="flex items-center gap-3 mb-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"
                                        class="w-6 h-6 @if ($shippingMethod === 'courier') text-[var(--accent)] @else text-gray-400 @endif">
                                        <path d="M3 4.5A1.5 1.5 0 0 1 4.5 3h9A1.5 1.5 0 0 1 15 4.5v1h1a3 3 0 0 1 3 3v1.5a1.5 1.5 0 0 1-1.5 1.5H17v1.5A1.5 1.5 0 0 1 15.5 13H15v-1a2 2 0 0 0-2-2s-1.126 0-2 2h-2c-.874-2-2-2-2-2a2 2 0 0 0-2 2v1H4.5A1.5 1.5 0 0 1 3 11.5v-7ZM6 11a1 1 0 1 0 0-2 1 1 0 0 0 0 2Zm8 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" />
                                    </svg>
                                    <span class="font-semibold text-gray-900">Futárral kiszállítás</span>
                                </div>
                                <div class="text-sm">
                                    @if ($subTotal >= $freeShippingThreshold)
                                        <span class="text-green-600 font-semibold">Ingyenes</span>
                                        <span class="text-gray-400 ml-1 line-through">{{ number_format($courierPrice, 0, ',', ' ') }} Ft</span>
                                    @else
                                        <span class="font-semibold text-gray-900">{{ number_format($courierPrice, 0, ',', ' ') }} Ft</span>
                                    @endif
                                </div>
                            </div>
                        </label>

                        {{-- Pickup --}}
                        <label class="block cursor-pointer">
                            <input type="radio" wire:model.live="shippingMethod" value="pickup" class="sr-only">
                            <div class="p-4 rounded-lg border-2 transition-all
                                @if ($shippingMethod === 'pickup') border-[var(--accent)] bg-[var(--accent)]/5 @else border-gray-200 hover:border-gray-300 @endif">
                                <div class="flex items-center gap-3 mb-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"
                                        class="w-6 h-6 @if ($shippingMethod === 'pickup') text-[var(--accent)] @else text-gray-400 @endif">
                                        <path fill-rule="evenodd" d="M9.69 8.571C9.817 7.702 9.817 6.823 9.75 6h.5c.066.823.066 1.702-.0625 2.571zM8.75 6c-.017.823-.017 1.702.0625 2.571H7.5C7.433 7.702 7.433 6.823 7.5 6h1.25zM9.25 11.5c.24.7.576 1.389 1 2H6.063c-.333-.584-.657-1.284-.938-2h4.125zm3.5-3.929c-.23.664-.523 1.312-.873 1.929h2.064c-.013-.641-.126-1.25-.373-1.929zM9.5 6h1v2.571H9.5V6zm-1.5 0h1v2.571H8V6zm2.5 0h1v2.571h-1V6z" clip-rule="evenodd" />
                                    </svg>
                                    <span class="font-semibold text-gray-900">Átvétel a telephelyen</span>
                                </div>
                                <div class="text-sm">
                                    <span class="text-green-600 font-semibold">Ingyenes</span>
                                </div>
                            </div>
                        </label>
                    </div>

                    {{-- Free shipping progress bar --}}
                    @if ($shippingMethod === 'courier' && $subTotal < $freeShippingThreshold)
                        <div class="bg-amber-50 border border-amber-200 rounded-lg p-3 mb-4">
                            <div class="flex justify-between text-sm mb-1.5">
                                <span class="text-amber-800 font-medium">Ingyen szállítás</span>
                                <span class="text-amber-700">{{ number_format($freeShippingThreshold - $subTotal, 0, ',', ' ') }} Ft-tól</span>
                            </div>
                            <div class="w-full bg-amber-200 rounded-full h-2">
                                <div class="bg-amber-500 h-2 rounded-full transition-all" style="width: {{ min(($subTotal / $freeShippingThreshold) * 100, 100) }}%"></div>
                            </div>
                        </div>
                    @endif

                    {{-- Navigation --}}
                    <div class="flex items-center justify-between mt-6">
                        <button type="button" wire:click="back('address')"
                            class="text-sm text-gray-600 hover:text-gray-800 font-medium">
                            ← Vissza
                        </button>
                        <button type="button" wire:click="continueToPayment" wire:loading.attr="disabled"
                            class="px-6 py-2.5 bg-[var(--accent)] text-white rounded-lg font-semibold hover:bg-[var(--accent-hover)] transition-colors disabled:opacity-60">
                            <span wire:loading.remove>Tovább</span>
                            <span wire:loading>Feldolgozás...</span>
                        </button>
                    </div>
                </div>
            </div>

            {{-- ============================================================
                 STEP 4: PAYMENT
                 ============================================================ --}}
            <div wire:show="step === 'payment'">
                <div class="bg-white rounded-lg shadow-sm p-6 space-y-6">
                    <div>
                        <h3 class="text-base font-semibold text-gray-900 mb-4">Fizetési mód</h3>

                        <div class="grid sm:grid-cols-2 gap-3">
                            {{-- Bank transfer --}}
                            <label class="block cursor-pointer">
                                <input type="radio" wire:model.live="paymentMethod" value="bank_transfer" class="sr-only">
                                <div class="p-4 rounded-lg border-2 transition-all
                                    @if ($paymentMethod === 'bank_transfer') border-[var(--accent)] bg-[var(--accent)]/5 @else border-gray-200 hover:border-gray-300 @endif">
                                    <div class="flex items-center gap-3 mb-1">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"
                                            class="w-6 h-6 @if ($paymentMethod === 'bank_transfer') text-[var(--accent)] @else text-gray-400 @endif">
                                            <path d="M4 4a2 2 0 0 0-2 2v1h12V6a2 2 0 0 0-2-2H4zm14 3H2v7a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7zM6 10h2v2H6v-2zm4 0h4v2h-4v-2z" />
                                        </svg>
                                        <span class="font-semibold text-gray-900">Előre utalás</span>
                                    </div>
                                    <p class="text-xs text-gray-500">Bankszámla-adatok a rendelés megerősítésében</p>
                                </div>
                            </label>

                            {{-- COD --}}
                            <label class="block cursor-pointer">
                                <input type="radio" wire:model.live="paymentMethod" value="cash_on_delivery" class="sr-only">
                                <div class="p-4 rounded-lg border-2 transition-all
                                    @if ($paymentMethod === 'cash_on_delivery') border-[var(--accent)] bg-[var(--accent)]/5 @else border-gray-200 hover:border-gray-300 @endif">
                                    <div class="flex items-center gap-3 mb-1">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"
                                            class="w-6 h-6 @if ($paymentMethod === 'cash_on_delivery') text-[var(--accent)] @else text-gray-400 @endif">
                                            <path fill-rule="evenodd" d="M4 4a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2H4zm2.5 3a1.5 1.5 0 1 0 0 3 1.5 1.5 0 0 0 0-3zM13 8.5a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 0 1h-1A.5.5 0 0 1 13 8.5zm.5 1.5a.5.5 0 0 0 0 1h1a.5.5 0 0 0 0-1h-1z" clip-rule="evenodd" />
                                        </svg>
                                        <span class="font-semibold text-gray-900">Fizetés átvételkor</span>
                                    </div>
                                    <p class="text-xs text-gray-500">Fizess a futár kezébe kézben</p>
                                </div>
                            </label>
                        </div>
                    </div>

                    {{-- Notes --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Megjegyzés a rendeléshez (nem kötelező)</label>
                        <textarea wire:model="notes" rows="2"
                            class="w-full px-4 py-2.5 rounded-lg border border-[var(--border-subtle)] bg-white text-[var(--text-main)] placeholder-[var(--text-muted)] focus:outline-none focus:border-[var(--accent)] focus:ring-1 focus:ring-[var(--accent)] resize-none"
                            ></textarea>
                    </div>

                    {{-- Order review --}}
                    <div class="border-t border-gray-200 pt-4">
                        <h3 class="text-base font-semibold text-gray-900 mb-3">Rendelés összefoglaló</h3>

                        <div class="space-y-2 text-sm">
                            @foreach ($cartItems as $item)
                                <div class="flex justify-between text-gray-600">
                                    <span>{{ $item->name }} × {{ $item->quantity }}</span>
                                    <span>{{ number_format($item->price * $item->quantity, 0, ',', ' ') }} Ft</span>
                                </div>
                            @endforeach

                            <div class="border-t border-gray-100 pt-2 mt-2 flex justify-between text-gray-600">
                                <span>Szállítás ({{ \App\Enums\ShippingMethod::from($shippingMethod)->getLabel() }})</span>
                                <span>@if ($shippingPrice === 0) <span class="text-green-600">Ingyenes</span> @else {{ number_format($shippingPrice, 0, ',', ' ') }} Ft @endif</span>
                            </div>

                            <div class="border-t border-gray-200 pt-2 flex justify-between font-semibold text-gray-900 text-base">
                                <span>Végösszeg</span>
                                <span>{{ number_format($totalPrice, 0, ',', ' ') }} Ft</span>
                            </div>
                        </div>

                        <div class="mt-3 text-xs text-gray-500">
                            Fizetési mód: {{ \App\Enums\PaymentMethod::from($paymentMethod)->getLabel() }}
                        </div>
                    </div>

                    {{-- Navigation --}}
                    <div class="flex items-center justify-between pt-2">
                        <button type="button" wire:click="back('shipping')"
                            class="text-sm text-gray-600 hover:text-gray-800 font-medium">
                            ← Vissza
                        </button>
                        <button type="button" wire:click="placeOrder" wire:loading.attr="disabled"
                            class="px-8 py-3 bg-[var(--accent)] text-white rounded-lg font-bold text-lg hover:bg-[var(--accent-hover)] transition-colors disabled:opacity-60">
                            <span wire:loading.remove>Megrendelés leadása</span>
                            <span wire:loading>Feldolgozás...</span>
                        </button>
                    </div>
                </div>
            </div>

        </div>

        {{-- RIGHT: Order summary sidebar --}}
        <div class="lg:col-span-1 mt-6 lg:mt-0">
            <div class="bg-white rounded-lg shadow-sm p-6 sticky top-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Összegzés</h2>

                <div class="space-y-3 max-h-48 overflow-y-auto mb-4">
                    @foreach ($cartItems as $item)
                        <div class="flex items-start gap-3" wire:key="summary-{{ $item->id }}">
                            @if ($item->attributes->image_url)
                                <img src="{{ $item->attributes->image_url }}" alt="{{ $item->name }}"
                                    class="w-12 h-12 object-cover rounded flex-shrink-0">
                            @else
                                <div class="w-12 h-12 bg-gray-100 rounded flex-shrink-0"></div>
                            @endif
                            <div class="flex-1 min-w-0">
                                <p class="text-sm text-gray-800 line-clamp-2">{{ $item->name }}</p>
                                <p class="text-xs text-gray-500">× {{ $item->quantity }}</p>
                            </div>
                            <span class="text-sm font-medium text-gray-900 flex-shrink-0">
                                {{ number_format($item->price * $item->quantity, 0, ',', ' ') }} Ft
                            </span>
                        </div>
                    @endforeach
                </div>

                <div class="space-y-2 border-t border-gray-200 pt-3">
                    <div class="flex justify-between text-gray-600 text-sm">
                        <span>Részösszeg</span>
                        <span>{{ number_format($subTotal, 0, ',', ' ') }} Ft</span>
                    </div>
                    <div class="flex justify-between text-gray-600 text-sm">
                        <span>Szállítás</span>
                        <span>
                            @if ($step === 'auth' || $step === 'address')
                                <span class="text-gray-400">Következő lépésben...</span>
                            @elseif ($shippingPrice === 0)
                                <span class="text-green-600">Ingyenes</span>
                            @else
                                {{ number_format($shippingPrice, 0, ',', ' ') }} Ft
                            @endif
                        </span>
                    </div>
                    <div class="border-t border-gray-200 pt-2">
                        <div class="flex justify-between font-semibold text-lg text-gray-900">
                            <span>Végösszeg</span>
                            <span>
                                @if ($step === 'auth' || $step === 'address')
                                    {{ number_format($subTotal, 0, ',', ' ') }} Ft
                                @else
                                    {{ number_format($totalPrice, 0, ',', ' ') }} Ft
                                @endif
                            </span>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">Az ár tartalmazza az ÁFA-t</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
