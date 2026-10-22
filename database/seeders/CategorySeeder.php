<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

/**
 * Garante que as categorias das seções dedicadas de artigo existam ("Mural",
 * "Notícia" e "SOS DPEMT" — cada uma com sua própria página pública),
 * sem duplicar caso o seeder seja executado mais de uma vez.
 */
class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Mural' => ['slug' => 'mural', 'color' => '#7C3AED'],
            'Notícia' => ['slug' => 'noticia', 'color' => '#2563EB'],
            'SOS DPEMT' => ['slug' => 'sos-dpemt', 'color' => '#DC2626'],
        ];

        foreach ($categories as $name => $data) {
            Category::firstOrCreate(['slug' => $data['slug']], ['name' => $name, 'color' => $data['color']]);
        }
    }
}
