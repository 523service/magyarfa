<?php

namespace Database\Factories\Shop;

use App\Models\Shop\MaterialBasePrice;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<MaterialBasePrice>
 */
class MaterialBasePriceFactory extends Factory
{
    protected $model = MaterialBasePrice::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->words(2, true);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'price_per_unit' => $this->faker->randomFloat(2, 50, 1000),
            'attribute_slug' => $this->faker->randomElement(['vastagsag', 'meret_liter', 'suly_kg']),
            'unit_label' => $this->faker->randomElement(['cm', 'liter', 'kg']),
            'description' => $this->faker->optional()->sentence(),
            'is_active' => true,
        ];
    }
}
