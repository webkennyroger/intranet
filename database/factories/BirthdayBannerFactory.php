<?php

namespace Database\Factories;

use App\Models\BirthdayBanner;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BirthdayBanner>
 */
class BirthdayBannerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'month' => fake()->unique()->numberBetween(1, 12),
            'image_path' => 'birthdays/'.fake()->uuid().'.jpg',
            'is_published' => true,
        ];
    }
}
