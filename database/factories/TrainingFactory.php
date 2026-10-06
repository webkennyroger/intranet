<?php

namespace Database\Factories;

use App\Models\Training;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Gera capacitações de teste com carga horária e data de início futuras, já publicadas.
 *
 * @extends Factory<Training>
 */
class TrainingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'external_url' => fake()->url(),
            'workload_hours' => fake()->numberBetween(2, 40),
            'starts_at' => fake()->dateTimeBetween('now', '+2 months'),
            'is_published' => true,
        ];
    }
}
