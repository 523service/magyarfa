<?php

namespace Database\Factories\Shop;

use App\Models\Shop\MaterialBasePrice;
use App\Models\Shop\SystemTemplate;
use App\Models\Shop\SystemTemplateItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SystemTemplateItem>
 */
class SystemTemplateItemFactory extends Factory
{
    protected $model = SystemTemplateItem::class;

    public function definition(): array
    {
        return [
            'system_template_id' => SystemTemplate::factory(),
            'material_price_id' => MaterialBasePrice::factory(),
            'label' => $this->faker->words(2, true),
            'quantity_type' => $this->faker->randomElement(['fixed', 'product_thickness_cm']),
            'quantity_value' => $this->faker->optional()->randomFloat(4, 0.5, 10),
            'sort_order' => $this->faker->numberBetween(0, 10),
        ];
    }
}
