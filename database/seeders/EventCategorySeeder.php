<?php

namespace Database\Seeders;

use App\Models\EventCategory;
use Illuminate\Database\Seeder;

/**
 * Categorias padrão de evento (chips arrastáveis na barra lateral do
 * calendário admin), sem duplicar caso o seeder seja executado mais de
 * uma vez.
 */
class EventCategorySeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            ['name' => 'Novo planejamento de eventos', 'color' => '#0ab39c'],
            ['name' => 'Reunião', 'color' => '#299cdb'],
            ['name' => 'Geração de relatórios', 'color' => '#f7b84b'],
            ['name' => 'Criar novo tema', 'color' => '#f06548'],
        ];

        foreach ($defaults as $order => $category) {
            EventCategory::firstOrCreate(['name' => $category['name']], ['color' => $category['color'], 'order' => $order]);
        }
    }
}
