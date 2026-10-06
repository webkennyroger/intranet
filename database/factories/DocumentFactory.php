<?php

namespace Database\Factories;

use App\Models\Document;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Gera documentos de teste já publicados, com um caminho de arquivo PDF fictício.
 *
 * @extends Factory<Document>
 */
class DocumentFactory extends Factory
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
            'file_path' => 'documents/'.fake()->uuid().'.pdf',
            'is_published' => true,
        ];
    }
}
