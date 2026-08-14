<?php

namespace App\Services\Pricing;

use App\Models\Shop\MaterialBasePrice;
use App\Models\Shop\Product;
use App\Models\Shop\SystemTemplate;
use InvalidArgumentException;

class ProductPriceCalculator
{
    public function calculate(Product $product): float
    {
        return match ($product->pricing_mode) {
            'manual' => (float) $product->price,
            'formula' => $this->calculateFormula($product),
            'system_template' => $this->calculateSystemTemplate($product),
            default => (float) ($product->price ?? 0),
        };
    }

    /** @return array<string, mixed> */
    public function explain(Product $product): array
    {
        return match ($product->pricing_mode) {
            'manual' => [
                'pricing_mode' => 'manual',
                'price' => (float) $product->price,
                'final_price' => (float) $product->price,
            ],
            'formula' => $this->explainFormula($product),
            'system_template' => $this->explainSystemTemplate($product),
            default => [
                'pricing_mode' => $product->pricing_mode ?? 'unknown',
                'final_price' => 0,
                'error' => 'Unknown pricing mode',
            ],
        };
    }

    public function recalculateProduct(Product $product): void
    {
        $price = $this->calculate($product);

        $product->calculated_price = $price;
        $product->price_calculated_at = now();
        $product->saveQuietly();
    }

    public function recalculateByMaterialPrice(MaterialBasePrice $materialPrice): int
    {
        $count = 0;

        // Products using formula with this material price
        Product::query()
            ->where('pricing_mode', 'formula')
            ->where('material_price_id', $materialPrice->id)
            ->each(function (Product $product) use (&$count): void {
                $this->recalculateProduct($product);
                $count++;
            });

        // Products using system_template that includes this material price
        $templateIds = $materialPrice->systemTemplateItems()
            ->pluck('system_template_id')
            ->unique();

        Product::query()
            ->where('pricing_mode', 'system_template')
            ->whereIn('system_template_id', $templateIds)
            ->each(function (Product $product) use (&$count): void {
                $this->recalculateProduct($product);
                $count++;
            });

        return $count;
    }

    public function recalculateBySystemTemplate(SystemTemplate $template): int
    {
        $count = 0;

        Product::query()
            ->where('pricing_mode', 'system_template')
            ->where('system_template_id', $template->id)
            ->each(function (Product $product) use (&$count): void {
                $this->recalculateProduct($product);
                $count++;
            });

        return $count;
    }

    private function calculateFormula(Product $product): float
    {
        return match ($product->formula_type) {
            'board_by_thickness_cm' => $this->boardByThickness($product),
            'fixed_unit_price' => $this->fixedUnitPrice($product),
            default => throw new InvalidArgumentException("Unknown formula_type: {$product->formula_type}"),
        };
    }

    private function boardByThickness(Product $product): float
    {
        $materialPrice = $product->materialPrice ?? $product->load('materialPrice')->materialPrice;

        if (! $materialPrice) {
            return 0.0;
        }

        return (float) $materialPrice->price_per_unit * (float) $product->thickness_cm;
    }

    private function fixedUnitPrice(Product $product): float
    {
        $materialPrice = $product->materialPrice ?? $product->load('materialPrice')->materialPrice;

        return $materialPrice ? (float) $materialPrice->price_per_unit : 0.0;
    }

    private function calculateSystemTemplate(Product $product): float
    {
        $template = $product->systemTemplate ?? $product->load('systemTemplate.items.materialPrice')->systemTemplate;

        if (! $template) {
            return 0.0;
        }

        if (! $template->relationLoaded('items')) {
            $template->load('items.materialPrice');
        }

        $total = 0.0;

        foreach ($template->items as $item) {
            if (! $item->materialPrice) {
                continue;
            }

            $unitPrice = (float) $item->materialPrice->price_per_unit;

            $quantity = match ($item->quantity_type) {
                'fixed' => (float) $item->quantity_value,
                'product_thickness_cm' => (float) $product->thickness_cm,
                default => 0.0,
            };

            $total += $unitPrice * $quantity;
        }

        return $total;
    }

    /** @return array<string, mixed> */
    private function explainFormula(Product $product): array
    {
        $materialPrice = $product->materialPrice ?? $product->load('materialPrice')->materialPrice;

        $unitPrice = $materialPrice ? (float) $materialPrice->price_per_unit : 0.0;
        $thickness = (float) $product->thickness_cm;
        $finalPrice = $this->calculateFormula($product);

        return [
            'pricing_mode' => 'formula',
            'formula_type' => $product->formula_type,
            'material' => $materialPrice?->name,
            'unit_price' => $unitPrice,
            'thickness_cm' => $thickness,
            'final_price' => $finalPrice,
        ];
    }

    /** @return array<string, mixed> */
    private function explainSystemTemplate(Product $product): array
    {
        $template = $product->systemTemplate ?? $product->load('systemTemplate.items.materialPrice')->systemTemplate;

        if (! $template) {
            return [
                'pricing_mode' => 'system_template',
                'error' => 'No system template assigned',
                'final_price' => 0,
            ];
        }

        if (! $template->relationLoaded('items')) {
            $template->load('items.materialPrice');
        }

        $lines = [];
        $total = 0.0;

        foreach ($template->items as $item) {
            if (! $item->materialPrice) {
                continue;
            }

            $unitPrice = (float) $item->materialPrice->price_per_unit;

            $quantity = match ($item->quantity_type) {
                'fixed' => (float) $item->quantity_value,
                'product_thickness_cm' => (float) $product->thickness_cm,
                default => 0.0,
            };

            $lineTotal = $unitPrice * $quantity;
            $total += $lineTotal;

            $lines[] = [
                'label' => $item->label,
                'unit_price' => $unitPrice,
                'quantity' => $quantity,
                'line_total' => $lineTotal,
                'source' => $item->quantity_type,
            ];
        }

        return [
            'pricing_mode' => 'system_template',
            'template' => $template->name,
            'thickness_cm' => (float) $product->thickness_cm,
            'lines' => $lines,
            'final_price' => $total,
        ];
    }
}
