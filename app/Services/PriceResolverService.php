<?php

namespace App\Services;

use App\Models\Shop\Product;

class PriceResolverService
{
    /**
     * Resolve the effective price for a product.
     *
     * Priority:
     *  1. Manual price (price > 0) – always wins
     *  2. product_price_components exist → sum of components
     *     Each component: price_per_unit × (attribute_slug ? product.attribute : fixed quantity)
     *  3. materialBasePrice set → price_per_unit × product.getAttribute(attribute_slug)
     *  4. Fallback: 0.0
     */
    public function resolve(Product $product): float
    {
        if ((float) $product->price > 0) {
            return (float) $product->price;
        }

        $components = $product->relationLoaded('priceComponents')
            ? $product->priceComponents
            : $product->priceComponents()->with('materialBasePrice')->get();

        if ($components->isNotEmpty()) {
            return $components->sum(function ($component) use ($product): float {
                $unitPrice = (float) $component->materialBasePrice->price_per_unit;
                $qty = $component->attribute_slug
                    ? $product->getAttributeNumericValue($component->attribute_slug)
                    : (float) $component->quantity;

                return $unitPrice * $qty;
            });
        }

        $basePrice = $product->relationLoaded('materialBasePrice')
            ? $product->materialBasePrice
            : $product->materialBasePrice()->first();

        if ($basePrice && $basePrice->attribute_slug) {
            $qty = $product->getAttributeNumericValue($basePrice->attribute_slug);
            if ($qty > 0) {
                return (float) $basePrice->price_per_unit * $qty;
            }
        }

        return 0.0;
    }

    /**
     * Format the resolved price as a Hungarian currency string.
     */
    public function format(Product $product): string
    {
        return number_format($this->resolve($product), 0, ',', ' ') . ' Ft';
    }
}
