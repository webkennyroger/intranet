<?php

use App\Models\ServiceLink;
use App\Models\User;

test('a guest is redirected to login from the services page', function () {
    $this->get('/servicos')->assertRedirect(route('login'));
});

test('an authenticated user sees active service links in order', function () {
    $user = User::factory()->create();
    ServiceLink::factory()->create(['title' => 'Primeiro', 'order' => 1, 'is_active' => true]);
    ServiceLink::factory()->create(['title' => 'Segundo', 'order' => 2, 'is_active' => true]);

    $response = $this->actingAs($user)->get('/servicos');

    $response->assertStatus(200)
        ->assertSeeInOrder(['Primeiro', 'Segundo']);
});

test('an inactive service link does not appear on the public page', function () {
    $user = User::factory()->create();
    ServiceLink::factory()->create(['title' => 'Visível', 'is_active' => true]);
    ServiceLink::factory()->create(['title' => 'Escondido', 'is_active' => false]);

    $response = $this->actingAs($user)->get('/servicos');

    $response->assertSee('Visível')->assertDontSee('Escondido');
});

test('service link renders as a new-tab external link', function () {
    $user = User::factory()->create();
    ServiceLink::factory()->create(['title' => 'Teste', 'url' => 'https://exemplo.gov.br']);

    $response = $this->actingAs($user)->get('/servicos');

    $response->assertSee('target="_blank"', false)
        ->assertSee('https://exemplo.gov.br', false);
});

test('a service link title is escaped in the rendered page', function () {
    $user = User::factory()->create();
    ServiceLink::factory()->create(['title' => '<script>alert(1)</script>']);

    $response = $this->actingAs($user)->get('/servicos');

    $response->assertDontSee('<script>alert(1)</script>', false);
});
