<?php

namespace App\Services;

use App\Models\Shop\Product;
use App\Models\Shop\Unit;
use Darryldecode\Cart\CartCollection;
use Darryldecode\Cart\Facades\CartFacade as Cart;
use Darryldecode\Cart\ItemCollection;

class CartService
{
    /**
     * Add item to cart.
     *
     * @param  array<string, mixed>  $productAttributes
     * @return array{rowId: string, item: ItemCollection}
     */
    public function addItem(
        Product $product,
        float $quantity,
        ?int $unitId = null,
        array $productAttributes = [],
        ?float $secondaryQty = null,
        ?string $secondaryUnit = null,
        ?string $baseUnit = null,
        ?float $overridePrice = null
    ): array {
        $unit = $this->resolveUnit($product, $unitId);
        $rowId = $this->generateRowId($product->id, $unit->id, $productAttributes);

        Cart::add([
            'id' => $rowId,
            'name' => $product->name,
            'price' => $overridePrice ?? $product->price,
            'quantity' => $quantity,
            'attributes' => [
                'product_id' => $product->id,
                'unit_id' => $unit->id,
                'unit_name' => $baseUnit ?? $unit->name,
                'base_unit' => $baseUnit ?? $unit->name,
                'secondary_qty' => $secondaryQty,
                'secondary_unit' => $secondaryUnit,
                'product_attributes' => $productAttributes,
                'image_url' => $product->getMainImageUrl('thumb'),
                'slug' => $product->slug,
            ],
            'associatedModel' => Product::class,
        ]);

        return [
            'rowId' => $rowId,
            'item' => Cart::get($rowId),
        ];
    }

    /**
     * Update quantity for a cart item.
     */
    public function updateQuantity(string $rowId, int $quantity): bool
    {
        if (! Cart::has($rowId)) {
            return false;
        }

        Cart::update($rowId, [
            'quantity' => [
                'relative' => false,
                'value' => $quantity,
            ],
        ]);

        return true;
    }

    /**
     * Remove item from cart.
     */
    public function removeItem(string $rowId): bool
    {
        return Cart::remove($rowId);
    }

    /**
     * Get cart contents.
     */
    public function getContent(): CartCollection
    {
        return Cart::getContent();
    }

    /**
     * Get number of unique items in cart.
     */
    public function getItemCount(): int
    {
        return $this->getContent()->count();
    }

    /**
     * Get total quantity of all items.
     */
    public function getTotalQuantity(): int
    {
        return Cart::getTotalQuantity();
    }

    /**
     * Get cart subtotal.
     */
    public function getSubTotal(): float
    {
        return (float) Cart::getSubTotal(false);
    }

    /**
     * Get cart total (with conditions applied).
     */
    public function getTotal(): float
    {
        return (float) Cart::getTotal();
    }

    /**
     * Clear the cart.
     */
    public function clear(): bool
    {
        return Cart::clear();
    }

    /**
     * Check if cart is empty.
     */
    public function isEmpty(): bool
    {
        return Cart::isEmpty();
    }

    /**
     * Get a specific cart item.
     */
    public function getItem(string $rowId): ?ItemCollection
    {
        return Cart::get($rowId);
    }

    /**
     * Check if item exists in cart.
     */
    public function hasItem(string $rowId): bool
    {
        return Cart::has($rowId);
    }

    /**
     * Generate unique row ID based on product, unit, and attributes.
     *
     * @param  array<string, mixed>  $attributes
     */
    protected function generateRowId(int $productId, int $unitId, array $attributes = []): string
    {
        $attributeHash = empty($attributes) ? '' : '-' . substr(md5(serialize($attributes)), 0, 8);

        return sprintf('%d-%d%s', $productId, $unitId, $attributeHash);
    }

    /**
     * Resolve unit - use provided unitId or fall back to product's primary unit.
     */
    protected function resolveUnit(Product $product, ?int $unitId): Unit
    {
        if ($unitId !== null) {
            $unit = $product->units()->where('shop_units.id', $unitId)->first();
            if ($unit) {
                return $unit;
            }
        }

        // Fall back to primary unit
        $primaryUnit = $product->units()->wherePivot('is_primary', true)->first();
        if ($primaryUnit) {
            return $primaryUnit;
        }

        // Fall back to first available unit or create a default
        $firstUnit = $product->units()->first();
        if ($firstUnit) {
            return $firstUnit;
        }

        // Return a default unit (db)
        return Unit::firstOrCreate(['name' => 'db']);
    }
}
