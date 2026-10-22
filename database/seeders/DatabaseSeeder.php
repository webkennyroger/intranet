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
     * fixas de Documentos e Capacitações (DocumentCategorySeeder,
     * TrainingCategorySeeder), os chips de evento padrão do calendário admin
     * (EventCategorySeeder), os links padrão da página de Serviços
     * (ServiceLinkSeeder), os processos reais do Conselho Superior
     * (CouncilProcessSeeder), o conteúdo real que alimenta a página Início
     * (HomeContentSeeder) e dados de demonstração pros módulos que ainda
     * ficariam vazios — documentos, capacitações, vídeos, fórum,
     * comentários etc. (DemoContentSeeder).
     */
    public function run(): void
    {
        $this->call(AclSeeder::class);
        $this->call(CategorySeeder::class);
        $this->call(DocumentCategorySeeder::class);
        $this->call(TrainingCategorySeeder::class);
        $this->call(EventCategorySeeder::class);
        $this->call(ServiceLinkSeeder::class);
        $this->call(CouncilProcessSeeder::class);
        $this->call(HomeContentSeeder::class);
        $this->call(DemoContentSeeder::class);
    }
}
