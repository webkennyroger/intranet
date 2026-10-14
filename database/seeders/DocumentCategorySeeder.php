<?php

namespace Database\Seeders;

use App\Models\DocumentCategory;
use Illuminate\Database\Seeder;

/**
 * Garante que as pastas iniciais de Documentos existam (Resoluções,
 * Contratos etc.), sem duplicar caso o seeder seja executado mais de uma
 * vez. O admin pode criar, editar e excluir outras pastas pela aba
 * "Pastas" de Documentos — esta lista é só o ponto de partida.
 */
class DocumentCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Resoluções' => ['icon' => 'scale-balanced', 'color' => '#2563EB'],
            'Contratos' => ['icon' => 'file-contract', 'color' => '#7C3AED'],
            'Instruções Normativas' => ['icon' => 'file-signature', 'color' => '#D97706'],
            'Convênios' => ['icon' => 'handshake', 'color' => '#059669'],
            'Cartilhas' => ['icon' => 'book-open', 'color' => '#DB2777'],
            'Mapas de Processo' => ['icon' => 'diagram-project', 'color' => '#0891B2'],
            'Plantões' => ['icon' => 'calendar-check', 'color' => '#DC2626'],
            'Plano de Atuação' => ['icon' => 'bullseye', 'color' => '#4F46E5'],
            'Plano Estratégico' => ['icon' => 'chart-line', 'color' => '#65A30D'],
        ];

        foreach ($categories as $name => $data) {
            DocumentCategory::updateOrCreate(
                ['slug' => str($name)->slug()->toString()],
                ['name' => $name, 'icon' => $data['icon'], 'color' => $data['color']],
            );
        }
    }
}
