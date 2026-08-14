<?php

namespace Database\Factories\Shop;

use App\Models\Shop\Attribute;
use App\Models\Shop\Product;
use App\Models\Shop\ProductAttributeValue;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductAttributeValue>
 */
class ProductAttributeValueFactory extends Factory
{
    protected $model = ProductAttributeValue::class;

    public function definition(): array
    {
        return [
            'shop_product_id' => Product::factory(),
            'shop_attribute_id' => Attribute::factory(),
            'text_value' => null,
            'number_value' => null,
            'boolean_value' => null,
        ];
    }

    public function withNumber(float $value): static
    {
        return $this->state(fn () => ['number_value' => $value, 'text_value' => null]);
    }

    public function withText(string $value): static
    {
        return $this->state(fn () => ['text_value' => $value, 'number_value' => null]);
    }
}
