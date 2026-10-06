<?php

namespace Database\Factories;

use App\Models\CouncilMember;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Gera membros de teste do Conselho Superior, ativos por padrão e sem foto.
 *
 * @extends Factory<CouncilMember>
 */
class CouncilMemberFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'role' => fake()->jobTitle(),
            'photo_path' => null,
            'order' => fake()->numberBetween(1, 20),
            'is_active' => true,
        ];
    }
}
