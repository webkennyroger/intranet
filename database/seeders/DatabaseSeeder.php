<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * Dispara os seeders essenciais para a intranet funcionar: papéis/permissões
     * (AclSeeder) e as categorias padrão do Mural (CategorySeeder).
     */
    public function run(): void
    {
        $this->call(AclSeeder::class);
        $this->call(CategorySeeder::class);
    }
}
