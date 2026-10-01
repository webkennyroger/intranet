<?php

use App\Models\User;

test('a guest is redirected to login when visiting the dashboard', function () {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});

test('an authenticated user sees the header and sidebar on the dashboard', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertStatus(200)
        ->assertSee('Intranet DPEMT')
        ->assertSee($user->name);
});
