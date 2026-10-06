<?php

use App\Models\User;

test('a guest is redirected to login when visiting inicio', function () {
    $this->get(route('inicio'))->assertRedirect(route('login'));
});

test('an authenticated user sees the header and sidebar on inicio', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('inicio'));

    $response->assertStatus(200)
        ->assertSee('Intranet DPEMT')
        ->assertSee($user->name);
});
