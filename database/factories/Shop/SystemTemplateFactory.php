<?php

namespace Database\Factories\Shop;

use App\Models\Shop\SystemTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<SystemTemplate>
 */
class SystemTemplateFactory extends Factory
{
    protected $model = SystemTemplate::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->words(3, true);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'is_active' => true,
            'notes' => $this->faker->optional()->sentence(),
        ];
    }
}
