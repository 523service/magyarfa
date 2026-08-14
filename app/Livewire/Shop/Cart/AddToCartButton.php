<?php

namespace App\Livewire\Shop\Cart;

use App\Models\Shop\Product;
use App\Models\Shop\ProductUnitConfig;
use App\Models\Shop\Unit;
use App\Services\CartService;
use Illuminate\Support\Collection;
use Livewire\Component;

class AddToCartButton extends Component
{
    public Product $product;

    public float $quantity = 1;

    public ?int $unitId = null;

    public int $maxQuantity = 100;

    /** @var array<string, mixed> */
    public array $productAttributes = [];

    /** @var Collection<int, Unit> */
    public Collection $units;

    // Unit config
    public ?ProductUnitConfig $unitConfig = null;

    public string $selectedUnit = 'base'; // 'base' vagy 'secondary'

    public float $minQty = 1;

    public float $stepQty = 1;

    public ?float $secondaryUnitQty = null;

    public string $baseUnitLabel = 'db';

    public ?string $secondaryUnitLabel = null;

    public float $pricePerUnit = 0;

    // Kalkulált értékek
    public float $actualBaseQty = 1;

    public ?float $secondaryQtyDisplay = null;

    public float $totalPrice = 0;

    public bool $wasRoundedUp = false;

    public function mount(Product $product): void
    {
        $this->product = $product;
        $this->maxQuantity = $product->qty ?? 100;
        $this->units = $product->units;

        $resolvedPrice = $product->getResolvedPrice();

        $config = $product->unitConfig()->with(['baseUnit', 'secondaryUnit'])->first();

        if ($config) {
            $this->unitConfig = $config;
            $this->minQty = (float) $config->min_order_qty;
            $this->stepQty = (float) $config->order_step;
            $this->secondaryUnitQty = $config->secondary_unit_qty ? (float) $config->secondary_unit_qty : null;
            $this->baseUnitLabel = $config->baseUnit->label_short ?: $config->baseUnit->name;
            $this->secondaryUnitLabel = $config->secondaryUnit?->label_short ?: $config->secondaryUnit?->name;
            $this->pricePerUnit = $config->price_per_base_unit !== null
                ? (float) $config->price_per_base_unit
                : $resolvedPrice;
            $this->quantity = $this->minQty;
        } else {
            $this->pricePerUnit = $resolvedPrice;
            $primaryUnit = $product->units()->wherePivot('is_primary', true)->first();
            $this->unitId = $primaryUnit?->id ?? $product->units->first()?->id;
        }

        $this->recalculate();
    }

    public function updatedQuantity(): void
    {
        $this->recalculate();
    }

    public function updatedSelectedUnit(): void
    {
        $this->quantity = $this->selectedUnit === 'secondary' ? 1 : $this->minQty;
        $this->recalculate();
    }

    public function incrementQuantity(): void
    {
        $step = ($this->selectedUnit === 'secondary') ? 1 : $this->stepQty;
        $nextQty = (float) $this->quantity + $step;

        // Alap egységben számolt következő mennyiség a maxQuantity ellenőrzéshez
        $nextBaseQty = ($this->unitConfig && $this->selectedUnit === 'secondary' && $this->secondaryUnitQty)
            ? $nextQty * $this->secondaryUnitQty
            : $nextQty;

        if ($nextBaseQty <= $this->maxQuantity) {
            $this->quantity = $nextQty;
            $this->recalculate();
        }
    }

    public function decrementQuantity(): void
    {
        $step = ($this->selectedUnit === 'secondary') ? 1 : $this->stepQty;
        $min = ($this->selectedUnit === 'secondary') ? 1 : $this->minQty;
        $this->quantity = max($min, (float) $this->quantity - $step);
        $this->recalculate();
    }

    public function addToCart(CartService $cartService): void
    {
        $qtyForCart = $this->unitConfig ? $this->actualBaseQty : (float) $this->quantity;

        if ($qtyForCart <= 0) {
            return;
        }

        $result = $cartService->addItem(
            $this->product,
            $qtyForCart,
            $this->unitId,
            $this->productAttributes,
            $this->unitConfig ? $this->secondaryQtyDisplay : null,
            $this->unitConfig ? $this->secondaryUnitLabel : null,
            $this->unitConfig ? $this->baseUnitLabel : null,
            $this->unitConfig ? $this->pricePerUnit : null,
        );

        $unit = $this->units->firstWhere('id', $this->unitId);

        $this->dispatch('cart-updated');

        $this->dispatch('item-added-to-cart', [
            'productId' => $this->product->id,
            'productName' => $this->product->name,
            'productImage' => $this->product->getFirstMediaUrl('product-images'),
            'quantity' => $qtyForCart,
            'unitName' => $this->unitConfig ? $this->baseUnitLabel : ($unit?->name ?? 'db'),
            'price' => $this->pricePerUnit,
            'rowId' => $result['rowId'],
        ]);
    }

    protected function recalculate(): void
    {
        if (! $this->unitConfig) {
            return;
        }

        $requestedQty = (float) $this->quantity;

        if ($this->selectedUnit === 'secondary' && $this->secondaryUnitQty) {
            $requestedQty *= $this->secondaryUnitQty;
        }

        $this->actualBaseQty = $this->unitConfig->roundUpToStep($requestedQty);
        $this->secondaryQtyDisplay = $this->unitConfig->toSecondaryUnit($this->actualBaseQty);
        $this->wasRoundedUp = $this->actualBaseQty > $requestedQty + 0.0001;
        $this->totalPrice = $this->actualBaseQty * $this->pricePerUnit;

        $this->dispatch(
            'unit-price-updated',
            totalPrice: $this->totalPrice,
            pricePerUnit: $this->pricePerUnit,
            baseUnitLabel: $this->baseUnitLabel,
        );
    }

    public function render()
    {
        return view('livewire.shop.cart.add-to-cart-button');
    }
}
