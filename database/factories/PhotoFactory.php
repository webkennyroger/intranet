<?php

namespace Database\Factories;

use App\Models\Photo;
use App\Models\PhotoAlbum;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Gera fotos de teste associadas a um álbum (criado via factory), com legenda e ordem.
 *
 * @extends Factory<Photo>
 */
class PhotoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'photo_album_id' => PhotoAlbum::factory(),
            'image_path' => 'photos/'.fake()->uuid().'.jpg',
            'caption' => fake()->sentence(3),
            'order' => fake()->numberBetween(1, 20),
        ];
    }
}
