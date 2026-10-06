<?php

namespace Database\Factories;

use App\Models\PhotoAlbum;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Gera álbuns de fotos de teste, já publicados e sem imagem de capa.
 *
 * @extends Factory<PhotoAlbum>
 */
class PhotoAlbumFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'cover_image_path' => null,
            'event_date' => fake()->date(),
            'is_published' => true,
        ];
    }
}
