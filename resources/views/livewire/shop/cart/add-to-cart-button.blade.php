<div class="product-add-to-cart" wire:key="add-to-cart-{{ $product->id }}">

    {{-- Controls row: quantity selector + unit toggle inline --}}
    <div class="atc-controls-row">
        <div class="quantity-selector">
            <button
                type="button"
                class="qty-btn"
                wire:click="decrementQuantity"
                @disabled($selectedUnit === 'secondary' ? $quantity <= 1 : $quantity <= $minQty)
            >
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M4 10a.75.75 0 0 1 .75-.75h10.5a.75.75 0 0 1 0 1.5H4.75A.75.75 0 0 1 4 10Z" clip-rule="evenodd" />
                </svg>
            </button>
            <input
                type="number"
                wire:model.live="quantity"
                min="{{ $selectedUnit === 'secondary' ? 1 : $minQty }}"
                step="{{ $selectedUnit === 'secondary' ? 1 : $stepQty }}"
                class="qty-input"
            >
            <button
                type="button"
                class="qty-btn"
                wire:click="incrementQuantity"
            >
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M10.75 4.75a.75.75 0 0 0-1.5 0v4.5h-4.5a.75.75 0 0 0 0 1.5h4.5v4.5a.75.75 0 0 0 1.5 0v-4.5h4.5a.75.75 0 0 0 0-1.5h-4.5v-4.5Z" />
                </svg>
            </button>
        </div>

        {{-- Unit Toggle: base/secondary – inline with qty --}}
        @if($unitConfig && $secondaryUnitLabel)
            <div class="atc-unit-toggle">
                <button
                    type="button"
                    class="atc-unit-btn {{ $selectedUnit === 'base' ? 'active' : '' }}"
                    wire:click="$set('selectedUnit', 'base')"
                >
                    {{ $baseUnitLabel }}
                </button>
                <button
                    type="button"
                    class="atc-unit-btn {{ $selectedUnit === 'secondary' ? 'active' : '' }}"
                    wire:click="$set('selectedUnit', 'secondary')"
                >
                    {{ $secondaryUnitLabel }}
                </button>
            </div>
        @endif

        {{-- Legacy unit selector – only when no unitConfig --}}
        @if(!$unitConfig && $units->count() > 0)
            <div class="unit-selector">
                <select wire:model.live="unitId" class="unit-select">
                    @foreach($units as $unit)
                        <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                    @endforeach
                </select>
            </div>
        @endif
    </div>

    {{-- Price summary card (only when unitConfig exists) --}}
    @if($unitConfig)
        <div class="atc-price-summary">
            <div class="atc-summary-main">
                <div class="atc-price-total">
                    {{ number_format($totalPrice, 0, ',', ' ') }} Ft
                </div>
                <div class="atc-price-per-unit">
                    {{ number_format($pricePerUnit, 0, ',', ' ') }} Ft / {{ $baseUnitLabel }}
                </div>
            </div>
            <div class="atc-summary-details">
                <div class="atc-price-breakdown">
                    <span class="atc-base-qty">{{ rtrim(rtrim(number_format($actualBaseQty, 4, ',', ' '), '0'), ',') }} {{ $baseUnitLabel }}</span>
                    @if($secondaryQtyDisplay !== null)
                        <span class="atc-sep">·</span>
                        <span>{{ (int) $secondaryQtyDisplay }} {{ $secondaryUnitLabel }}</span>
                    @endif
                </div>
                @if($wasRoundedUp)
                    <div class="atc-rounding-notice">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495ZM10 5a.75.75 0 0 1 .75.75v3.5a.75.75 0 0 1-1.5 0v-3.5A.75.75 0 0 1 10 5Zm0 9a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd" />
                        </svg>
                        Felfelé kerekítve – teljes csomagot szállítunk
                    </div>
                @endif
            </div>
        </div>
    @endif

    <button
        type="button"
        class="btn-add-to-cart"
        wire:click="addToCart"
        wire:loading.attr="disabled"
        @if($maxQuantity < 1) disabled @endif
    >
        <span wire:loading.remove wire:target="addToCart">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                <path d="M1 1.75A.75.75 0 0 1 1.75 1h1.628a1.75 1.75 0 0 1 1.734 1.51L5.18 3a65.25 65.25 0 0 1 13.36 1.412.75.75 0 0 1 .58.875 48.645 48.645 0 0 1-1.618 6.2.75.75 0 0 1-.712.513H6a2.503 2.503 0 0 0-2.292 1.5H17.25a.75.75 0 0 1 0 1.5H2.76a.75.75 0 0 1-.748-.807 4.002 4.002 0 0 1 2.716-3.486L3.626 2.716a.25.25 0 0 0-.248-.216H1.75A.75.75 0 0 1 1 1.75ZM6 17.5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0ZM15.5 19a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3Z" />
            </svg>
            Kosárba
        </span>
        <span wire:loading wire:target="addToCart">
            <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Hozzáadás...
        </span>
    </button>
</div>