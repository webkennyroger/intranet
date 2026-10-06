<?php

use App\Models\User;

test('a guest visiting the root is redirected to login', function () {
    $this->get('/')->assertRedirect(route('login'));
});

test('an authenticated user visiting the root is redirected to inicio', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/')->assertRedirect(route('inicio'));
});
