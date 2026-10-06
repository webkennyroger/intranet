<?php

/**
 * Itens do menu lateral do painel administrativo.
 *
 * Cada item é um array com 'label' (texto exibido), 'icon' (nome do ícone
 * Font Awesome), 'route' (nome da rota Laravel) e 'permission' (permissão
 * exigida para o item aparecer; null = visível para qualquer admin logado).
 *
 * Itens podem vir soltos no topo (ex.: "Painel") ou agrupados em seções com
 * 'group' (título do grupo), 'icon'/'defaultOpen' (ícone e estado inicial do
 * grupo) e 'items' (lista de itens do grupo): CONTEÚDO, DEFENSORES, GESTÃO,
 * OUTROS e ADMINISTRAÇÃO.
 *
 * O campo opcional 'alias' => true marca um item que aponta para a mesma rota
 * de outro já listado (ex.: "Notícias" e "SOS DPEMT" both apontam para
 * admin.articles.index) — serve só para o menu não tentar marcar dois itens
 * como "ativos" ao mesmo tempo nem duplicar a navegação de fato.
 */
return [
    ['label' => 'Painel', 'icon' => 'gauge', 'route' => 'dashboard', 'permission' => null],
    [
        'group' => 'GERAL',
        'items' => [
            ['label' => 'Assistente Geral', 'icon' => 'robot', 'route' => 'admin.placeholder.assistente-geral', 'permission' => null],
        ],
    ],
    [
        'group' => 'CONTEÚDO',
        'icon' => 'folder-open',
        'defaultOpen' => false,
        'items' => [
            ['label' => 'Mural', 'icon' => 'newspaper', 'route' => 'admin.articles.index', 'permission' => 'content.view'],
            ['label' => 'Serviços', 'icon' => 'sliders', 'route' => 'admin.services.index', 'permission' => 'services.manage'],
            ['label' => 'Documentos', 'icon' => 'file-lines', 'route' => 'admin.documents.index', 'permission' => 'content.view'],
        ],
    ],

    [
        'group' => 'DEFENSORES',
        'icon' => 'user-tie',
        'defaultOpen' => false,
        'items' => [
            ['label' => 'Conselho Superior', 'icon' => 'user-tie', 'route' => 'admin.council.index', 'permission' => 'council.manage'],
        ],
    ],
    [
        'group' => 'GESTÃO',
        'icon' => 'briefcase',
        'defaultOpen' => false,
        'items' => [
            ['label' => 'Notícias', 'icon' => 'comment-dots', 'route' => 'admin.articles.index', 'permission' => 'content.view', 'alias' => true],
            ['label' => 'Fotos', 'icon' => 'images', 'route' => 'admin.photos.index', 'permission' => 'content.view'],
            ['label' => 'Vídeos', 'icon' => 'video', 'route' => 'admin.videos.index', 'permission' => 'content.view'],
        ],
    ],
    [
        'group' => 'OUTROS',
        'icon' => 'grip',
        'defaultOpen' => false,
        'items' => [
            ['label' => 'Eventos', 'icon' => 'calendar-days', 'route' => 'admin.events.index', 'permission' => 'content.view'],
            ['label' => 'Capacitações', 'icon' => 'graduation-cap', 'route' => 'admin.trainings.index', 'permission' => 'content.view'],
            ['label' => 'Fórum', 'icon' => 'bullhorn', 'route' => 'admin.forum.index', 'permission' => 'forum.manage'],
            ['label' => 'SOS DPEMT', 'icon' => 'life-ring', 'route' => 'admin.articles.index', 'permission' => 'content.view', 'alias' => true],
            ['label' => 'Aniversariantes', 'icon' => 'cake-candles', 'route' => 'birthdays.index', 'permission' => null],
        ],
    ],
    [
        'group' => 'ADMINISTRAÇÃO',
        'icon' => 'gear',
        'defaultOpen' => false,
        'items' => [
            ['label' => 'Aparência', 'icon' => 'palette', 'route' => 'admin.appearance.edit', 'permission' => 'settings.manage'],
            ['label' => 'Usuários', 'icon' => 'users', 'route' => 'admin.users.index', 'permission' => 'users.view'],
            ['label' => 'Histórico de Atividades', 'icon' => 'clock-rotate-left', 'route' => 'admin.activity-log.index', 'permission' => 'activity-log.view'],
        ],
    ],
];
