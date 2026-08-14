<?php

namespace Database\Factories\Shop;

use App\Models\Shop\Attribute;
use App\Models\Shop\AttributeOption;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AttributeOption>
 */
class AttributeOptionFactory extends Factory
{
    protected $model = AttributeOption::class;

    public function definition(): array
    {
        return [
            'shop_attribute_id' => Attribute::factory(),
            'value' => $this->faker->word(),
            'sort_order' => $this->faker->numberBetween(0, 100),
        ];
    }
}
