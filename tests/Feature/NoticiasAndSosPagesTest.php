<?php

use App\Models\Article;
use App\Models\Category;
use App\Models\User;
use Database\Seeders\CategorySeeder;

test('the notícias page only shows published articles in the Notícia category', function () {
    $this->seed(CategorySeeder::class);
    $user = User::factory()->create();
    $noticiaCategory = Category::where('slug', 'noticia')->first();
    $otherCategory = Category::factory()->create();

    Article::factory()->create(['title' => 'Notícia Oficial', 'category_id' => $noticiaCategory->id, 'is_published' => true, 'published_at' => now()]);
    Article::factory()->create(['title' => 'Artigo de Outra Categoria', 'category_id' => $otherCategory->id, 'is_published' => true, 'published_at' => now()]);
    Article::factory()->create(['title' => 'Notícia Rascunho', 'category_id' => $noticiaCategory->id, 'is_published' => false]);

    $this->actingAs($user)->get(route('noticias.index'))
        ->assertOk()
        ->assertSee('Notícia Oficial')
        ->assertDontSee('Artigo de Outra Categoria')
        ->assertDontSee('Notícia Rascunho');
});

test('the sos page only shows published articles in the SOS DPEMT category', function () {
    $this->seed(CategorySeeder::class);
    $user = User::factory()->create();
    $sosCategory = Category::where('slug', 'sos-dpemt')->first();
    $otherCategory = Category::factory()->create();

    Article::factory()->create(['title' => 'Alerta Importante', 'category_id' => $sosCategory->id, 'is_published' => true, 'published_at' => now()]);
    Article::factory()->create(['title' => 'Artigo Fora do SOS', 'category_id' => $otherCategory->id, 'is_published' => true, 'published_at' => now()]);

    $this->actingAs($user)->get(route('sos.index'))
        ->assertOk()
        ->assertSee('Alerta Importante')
        ->assertDontSee('Artigo Fora do SOS');
});
