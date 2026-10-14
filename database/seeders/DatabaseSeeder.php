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
     * (AclSeeder), as categorias padrão do Mural (CategorySeeder), as pastas
     * fixas de Documentos (DocumentCategorySeeder), os links padrão da página
     * de Serviços (ServiceLinkSeeder) e o conteúdo real que alimenta a página
     * Início (HomeContentSeeder).
     */
    public function run(): void
    {
        $this->call(AclSeeder::class);
        $this->call(CategorySeeder::class);
        $this->call(DocumentCategorySeeder::class);
        $this->call(ServiceLinkSeeder::class);
        $this->call(HomeContentSeeder::class);
    }
}
