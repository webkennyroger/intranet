<?php

namespace Database\Factories;

use App\Models\CouncilProcess;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CouncilProcess>
 */
class CouncilProcessFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sei_number' => fake()->unique()->numerify('2025.0.######-#'),
            'subject' => fake()->sentence(6),
            'assigned_to' => fake()->name(),
            'interested_party' => fake()->name(),
            'external_url' => null,
            'is_published' => true,
        ];
    }
}
