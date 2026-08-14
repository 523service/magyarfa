<?php

namespace App\Actions\Shop;

use App\Models\Shop\Product;

class CloneProductAction
{
    public function handle(Product $product): Product
    {
        $product->load([
            'categories',
            'units',
            'unitConfig',
            'attributeValues.options',
            'priceComponents',
        ]);

        $clone = $product->replicate([
            'slug',
            'sku',
            'barcode',
            'calculated_price',
            'price_calculated_at',
        ]);

        $clone->name = $product->name . ' (Másolat)';
        $clone->slug = $this->generateUniqueSlug($product->slug);
        $clone->sku = null;
        $clone->barcode = null;
        $clone->is_visible = false;
        $clone->save();

        $clone->categories()->sync($product->categories->pluck('id'));

        $unitSync = $product->units->mapWithKeys(fn ($unit) => [
            $unit->id => ['is_primary' => $unit->pivot->is_primary],
        ])->all();
        $clone->units()->sync($unitSync);

        if ($product->unitConfig) {
            $clone->unitConfig()->create($product->unitConfig->only([
                'base_unit_id',
                'secondary_unit_id',
                'secondary_unit_qty',
                'min_order_qty',
                'order_step',
                'price_per_base_unit',
                'notes',
            ]));
        }

        foreach ($product->attributeValues as $attrValue) {
            $newValue = $clone->attributeValues()->create([
                'shop_attribute_id' => $attrValue->shop_attribute_id,
                'text_value' => $attrValue->text_value,
                'number_value' => $attrValue->number_value,
                'boolean_value' => $attrValue->boolean_value,
            ]);
            $newValue->options()->sync($attrValue->options->pluck('id'));
        }

        foreach ($product->priceComponents as $component) {
            $clone->priceComponents()->create([
                'material_base_price_id' => $component->material_base_price_id,
                'quantity' => $component->quantity,
                'attribute_slug' => $component->attribute_slug,
                'label' => $component->label,
                'sort_order' => $component->sort_order,
            ]);
        }

        return $clone;
    }

    private function generateUniqueSlug(string $baseSlug): string
    {
        $newSlug = $baseSlug . '-masolat';
        $counter = 2;

        while (Product::where('slug', $newSlug)->exists()) {
            $newSlug = $baseSlug . '-masolat-' . $counter;
            $counter++;
        }

        return $newSlug;
    }
}
