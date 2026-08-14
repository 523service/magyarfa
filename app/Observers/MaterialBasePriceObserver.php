<?php

namespace App\Observers;

use App\Models\Shop\MaterialBasePrice;
use App\Services\Pricing\ProductPriceCalculator;

class MaterialBasePriceObserver
{
    public function updated(MaterialBasePrice $materialBasePrice): void
    {
        if (! $materialBasePrice->wasChanged('price_per_unit')) {
            return;
        }

        app(ProductPriceCalculator::class)->recalculateByMaterialPrice($materialBasePrice);
    }
}
