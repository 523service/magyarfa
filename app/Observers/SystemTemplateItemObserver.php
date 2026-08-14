<?php

namespace App\Observers;

use App\Models\Shop\SystemTemplateItem;
use App\Services\Pricing\ProductPriceCalculator;

class SystemTemplateItemObserver
{
    public function saved(SystemTemplateItem $systemTemplateItem): void
    {
        $this->recalculate($systemTemplateItem);
    }

    public function deleted(SystemTemplateItem $systemTemplateItem): void
    {
        $this->recalculate($systemTemplateItem);
    }

    private function recalculate(SystemTemplateItem $systemTemplateItem): void
    {
        $template = $systemTemplateItem->systemTemplate;

        if ($template) {
            app(ProductPriceCalculator::class)->recalculateBySystemTemplate($template);
        }
    }
}
