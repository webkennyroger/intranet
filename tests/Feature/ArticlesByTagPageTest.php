<?php

use App\Models\Article;
use App\Models\Category;
use App\Models\User;

test('visiting a tag page shows published articles that have that tag', function () {
    $user = User::factory()->create();
    $concurso = Category::factory()->create(['name' => 'Concurso']);
    $licitacao = Category::factory()->create(['name' => 'Licitação']);
    $article = Article::factory()->hasAttached($concurso)->create(['title' => 'Com Tag', 'is_published' => true, 'published_at' => now()]);
    Article::factory()->hasAttached($licitacao)->create(['title' => 'Sem Tag', 'is_published' => true, 'published_at' => now()]);

    $response = $this->actingAs($user)->get(route('mural.tag', 'Concurso'));

    $response->assertStatus(200)->assertSee('Com Tag')->assertDontSee('Sem Tag');
});

test('a tag page shows articles from any category sharing that tag name is not mixed up with others', function () {
    $user = User::factory()->create();
    $estagio = Category::factory()->create(['name' => 'Estágio']);
    $outra = Category::factory()->create(['name' => 'Outra']);
    Article::factory()->hasAttached($estagio)->create(['title' => 'Com Estagio', 'is_published' => true, 'published_at' => now()]);
    Article::factory()->hasAttached($outra)->create(['title' => 'Com Outra', 'is_published' => true, 'published_at' => now()]);

    $response = $this->actingAs($user)->get(route('mural.tag', 'Estágio'));

    $response->assertSee('Com Estagio')->assertDontSee('Com Outra');
});

test('a tag page excludes unpublished articles even if tagged', function () {
    $user = User::factory()->create();
    $concurso = Category::factory()->create(['name' => 'Concurso']);
    Article::factory()->hasAttached($concurso)->create(['title' => 'Rascunho com Tag', 'is_published' => false]);

    $response = $this->actingAs($user)->get(route('mural.tag', 'Concurso'));

    $response->assertDontSee('Rascunho com Tag');
});

test('a tag with no matching articles shows the empty state', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('mural.tag', 'inexistente'));

    $response->assertStatus(200)->assertSee('Nenhuma matéria encontrada com esta tag.');
});

test('a guest is redirected to login from the tag page', function () {
    $this->get(route('mural.tag', 'Concurso'))->assertRedirect(route('login'));
});

test('the tag browsing page shows the "Explore por tags" widget on the side', function () {
    $user = User::factory()->create();
    $concurso = Category::factory()->create(['name' => 'Concurso']);
    Article::factory()->hasAttached($concurso)->create(['is_published' => true, 'published_at' => now()]);

    $response = $this->actingAs($user)->get(route('mural.tag', 'Concurso'));

    $response->assertSee('Explore por tags')->assertSee('href="'.route('mural.tag', 'Concurso').'"', false);
});

test('a tag with a special character in its name renders without breaking the page title', function () {
    $user = User::factory()->create();
    $category = Category::factory()->create(['name' => 'Sistemas & TI']);
    Article::factory()->hasAttached($category)->create(['title' => 'Com TI', 'is_published' => true, 'published_at' => now()]);

    $response = $this->actingAs($user)->get(route('mural.tag', 'Sistemas & TI'));

    $response->assertSee('Tag: #Sistemas & TI')->assertDontSee('&amp;amp;', false);
});
