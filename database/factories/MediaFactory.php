<?php

namespace Database\Factories;

use App\Models\Media;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Media>
 */
class MediaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->word().'.jpg',
            'path' => 'media/'.fake()->uuid().'.jpg',
            'source' => fake()->randomElement(['Mural', 'Documentos', 'Fotos', 'Conselho']),
            'user_id' => null,
        ];
    }
}
