<?php

namespace Database\Factories\Shop;

use App\Models\Shop\Attribute;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Attribute>
 */
class AttributeFactory extends Factory
{
    protected $model = Attribute::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->words(2, true);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'type' => $this->faker->randomElement(['text', 'number', 'select', 'multiselect', 'boolean']),
            'unit' => $this->faker->optional()->randomElement(['cm', 'kg', 'm', 'mm']),
            'is_required' => $this->faker->boolean(30),
            'is_filterable' => $this->faker->boolean(50),
            'is_visible' => $this->faker->boolean(80),
            'sort_order' => $this->faker->numberBetween(0, 100),
        ];
    }

    public function text(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'text',
            'unit' => null,
        ]);
    }

    public function number(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'number',
        ]);
    }

    public function select(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'select',
            'unit' => null,
        ]);
    }

    public function multiselect(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'multiselect',
            'unit' => null,
        ]);
    }

    public function boolean(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'boolean',
            'unit' => null,
        ]);
    }
}
