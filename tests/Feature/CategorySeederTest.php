<?php

use App\Models\Category;
use Database\Seeders\CategorySeeder;

test('CategorySeeder creates the Notícia and SOS DPEMT categories', function () {
    $this->seed(CategorySeeder::class);

    expect(Category::where('slug', 'noticia')->exists())->toBeTrue()
        ->and(Category::where('slug', 'sos-dpemt')->exists())->toBeTrue();
});

test('CategorySeeder is idempotent', function () {
    $this->seed(CategorySeeder::class);
    $this->seed(CategorySeeder::class);

    expect(Category::count())->toBe(2);
});
