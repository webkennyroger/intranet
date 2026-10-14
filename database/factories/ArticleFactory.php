<?php

namespace Database\Factories;

use App\Models\Article;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Gera artigos de teste para o Mural, com autor próprio (criado via
 * factory), sem nenhuma categoria vinculada e não publicados por padrão.
 */
class ArticleFactory extends Factory
{
    protected $model = Article::class;

    public function definition(): array
    {
        return [
            'author_id' => User::factory(),
            'title' => fake()->sentence(),
            'excerpt' => fake()->paragraph(),
            'body' => '<p>'.fake()->paragraph().'</p>',
            'cover_color' => '#064e3b',
            'cover_icon' => 'newspaper',
            'cover_label' => null,
            'featured_image' => null,
            'custom_fields' => null,
            'is_published' => false,
            'published_at' => null,
        ];
    }
}
