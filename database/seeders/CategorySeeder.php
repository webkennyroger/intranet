<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

/**
 * Garante que as categorias padrão do Mural ("Notícia" e "SOS DPEMT") existam,
 * sem duplicar caso o seeder seja executado mais de uma vez.
 */
class CategorySeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Notícia' => 'noticia', 'SOS DPEMT' => 'sos-dpemt'] as $name => $slug) {
            Category::firstOrCreate(['slug' => $slug], ['name' => $name]);
        }
    }
}
