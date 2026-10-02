<?php

namespace Database\Factories;

use App\Models\ServiceLink;
use Illuminate\Database\Eloquent\Factories\Factory;

class ServiceLinkFactory extends Factory
{
    protected $model = ServiceLink::class;

    public function definition(): array
    {
        return [
            'title' => fake()->unique()->words(3, true),
            'icon' => 'desktop',
            'url' => fake()->url(),
            'order' => 0,
            'is_active' => true,
        ];
    }
}
