<?php

use App\Models\ServiceLink;
use Database\Seeders\ServiceLinkSeeder;

test('ServiceLinkSeeder creates the 24 default service links', function () {
    $this->seed(ServiceLinkSeeder::class);

    expect(ServiceLink::count())->toBe(24)
        ->and(ServiceLink::where('title', 'Portal da Defensora e do Defensor Público')->exists())->toBeTrue()
        ->and(ServiceLink::where('title', 'Canal do Escutadoria')->exists())->toBeTrue();
});

test('ServiceLinkSeeder is idempotent', function () {
    $this->seed(ServiceLinkSeeder::class);
    $this->seed(ServiceLinkSeeder::class);

    expect(ServiceLink::count())->toBe(24);
});
