<?php

namespace Database\Factories;

use App\Enums\FeedbackStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Feedback>
 */
class FeedbackFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => null,
            'name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'description' => fake()->paragraph(),
            'url' => fake()->url(),
            'status' => fake()->randomElement(FeedbackStatus::cases()),
            'device_info' => [
                'ip' => fake()->ipv4(),
                'user_agent' => fake()->userAgent(),
                'accept_language' => 'hu-HU,hu;q=0.9',
                'screen_width' => fake()->randomElement([1920, 1440, 1280, 375, 390]),
                'screen_height' => fake()->randomElement([1080, 900, 800, 812, 844]),
            ],
            'screenshot' => null,
            'meta' => null,
        ];
    }
}
