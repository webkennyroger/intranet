<?php

namespace Database\Seeders;

use App\Models\TrainingCategory;
use Illuminate\Database\Seeder;

/**
 * Garante que as pastas iniciais de Capacitações existam, sem duplicar
 * caso o seeder seja executado mais de uma vez — mesmo padrão do
 * DocumentCategorySeeder.
 */
class TrainingCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Treinamento' => ['icon' => 'chalkboard-user', 'color' => '#2563EB'],
            'Curso' => ['icon' => 'graduation-cap', 'color' => '#059669'],
            'Workshop' => ['icon' => 'screwdriver-wrench', 'color' => '#D97706'],
            'Palestra' => ['icon' => 'microphone', 'color' => '#7C3AED'],
        ];

        foreach ($categories as $name => $data) {
            TrainingCategory::updateOrCreate(
                ['slug' => str($name)->slug()->toString()],
                ['name' => $name, 'icon' => $data['icon'], 'color' => $data['color']],
            );
        }
    }
}
