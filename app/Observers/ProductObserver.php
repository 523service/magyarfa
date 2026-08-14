<?php

namespace App\Observers;

use App\Models\Shop\Product;
use App\Services\Pricing\ProductPriceCalculator;

class ProductObserver
{
    private const PRICING_FIELDS = [
        'pricing_mode',
        'formula_type',
        'material_price_id',
        'system_template_id',
        'thickness_cm',
    ];

    public function saved(Product $product): void
    {
        if ($product->pricing_mode === 'manual' || $product->pricing_mode === null) {
            return;
        }

        $pricingChanged = collect(self::PRICING_FIELDS)->some(
            fn ($field) => $product->wasChanged($field)
        );

        if ($pricingChanged) {
            app(ProductPriceCalculator::class)->recalculateProduct($product);
        }
    }
}
