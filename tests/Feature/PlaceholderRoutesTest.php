<?php

use App\Models\User;

test('every placeholder sidebar route resolves to a 200 response for an authenticated user', function () {
    $user = User::factory()->create();

    foreach (config('sidebar') as $section) {
        foreach ($section['items'] ?? [$section] as $item) {
            if (! str_starts_with($item['route'], 'placeholder.')) {
                continue;
            }

            $this->actingAs($user)
                ->get($item['uri'])
                ->assertStatus(200)
                ->assertSee($item['label']);
        }
    }
});

test('a guest is redirected to login from a placeholder route', function () {
    $this->get('/mural')->assertRedirect(route('login'));
});
