<?php

namespace App\Services;

use Spatie\Permission\Models\Permission;

/**
 * Centraliza o registro de módulos administráveis (um por recurso real do
 * portal) e gera as permissões "{módulo}.{ação}" de cada um, servindo de
 * fonte única para o seeder de papéis e para a Matriz de Permissões.
 */
class PermissionManager
{
    public const ACTIONS = ['view', 'create', 'update', 'delete'];

    /**
     * Módulos administráveis do portal, na ordem em que aparecem na Matriz
     * de Permissões. Cada chave gera 4 permissões ("{chave}.view" etc.).
     *
     * @return array<string, array{label: string, icon: string}>
     */
    public static function modules(): array
    {
        return [
            'articles' => ['label' => 'Mural', 'icon' => 'newspaper'],
            'categories' => ['label' => 'Categorias', 'icon' => 'folder'],
            'media' => ['label' => 'Biblioteca de Mídia', 'icon' => 'photo-film'],
            'photos' => ['label' => 'Fotos', 'icon' => 'images'],
            'videos' => ['label' => 'Vídeos', 'icon' => 'video'],
            'documents' => ['label' => 'Documentos', 'icon' => 'file-lines'],
            'events' => ['label' => 'Eventos', 'icon' => 'calendar-days'],
            'trainings' => ['label' => 'Capacitações', 'icon' => 'graduation-cap'],
            'birthday-banners' => ['label' => 'Banners de Aniversariantes', 'icon' => 'cake-candles'],
            'services' => ['label' => 'Serviços', 'icon' => 'sliders'],
            'council' => ['label' => 'Conselho Superior', 'icon' => 'user-tie'],
            'forum' => ['label' => 'Fórum', 'icon' => 'bullhorn'],
            'users' => ['label' => 'Usuários', 'icon' => 'users'],
            'appearance' => ['label' => 'Aparência', 'icon' => 'palette'],
            'activity-log' => ['label' => 'Histórico de Atividades', 'icon' => 'clock-rotate-left'],
        ];
    }

    /**
     * Lista mestre de todas as permissões conhecidas pelo sistema: as 4 ações
     * de cada módulo acima, mais a permissão de gerenciar papéis em si (que
     * não é um módulo com matriz própria). Qualquer nova permissão precisa
     * ser adicionada aqui para existir no banco e poder ser atribuída a papéis.
     */
    public static function inventory(): array
    {
        $permissions = ['roles.manage'];

        foreach (array_keys(self::modules()) as $module) {
            foreach (self::ACTIONS as $action) {
                $permissions[] = "{$module}.{$action}";
            }
        }

        return $permissions;
    }

    public function ensureAll(): void
    {
        foreach (self::inventory() as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }
    }
}
