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
 * de outro já listado — serve só para o menu não tentar marcar dois itens
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
        'defaultOpen' => true,
        'items' => [
            ['label' => 'Notícias', 'icon' => 'comment-dots', 'route' => 'admin.articles.index', 'permission' => 'articles.view'],
            ['label' => 'Categorias', 'icon' => 'folder', 'route' => 'admin.categories.index', 'permission' => 'categories.view'],
            ['label' => 'Biblioteca de Mídia', 'icon' => 'photo-film', 'route' => 'admin.media.index', 'permission' => 'media.view'],
            ['label' => 'Fotos', 'icon' => 'images', 'route' => 'admin.photos.index', 'permission' => 'photos.view'],
            ['label' => 'Vídeos', 'icon' => 'video', 'route' => 'admin.videos.index', 'permission' => 'videos.view'],
            ['label' => 'Serviços', 'icon' => 'sliders', 'route' => 'admin.services.index', 'permission' => 'services.view'],
            ['label' => 'Documentos', 'icon' => 'file-lines', 'route' => 'admin.documents.index', 'permission' => 'documents.view'],
        ],
    ],

    [
        'group' => 'DEFENSORES',
        'icon' => 'user-tie',
        'defaultOpen' => true,
        'items' => [
            ['label' => 'Conselho Superior', 'icon' => 'user-tie', 'route' => 'admin.council.index', 'permission' => 'council.view'],
        ],
    ],
    [
        'group' => 'OUTROS',
        'icon' => 'grip',
        'defaultOpen' => true,
        'items' => [
            ['label' => 'Eventos', 'icon' => 'calendar-days', 'route' => 'admin.events.index', 'permission' => 'events.view'],
            ['label' => 'Capacitações', 'icon' => 'graduation-cap', 'route' => 'admin.trainings.index', 'permission' => 'trainings.view'],
            ['label' => 'Fórum', 'icon' => 'bullhorn', 'route' => 'admin.forum.index', 'permission' => 'forum.view'],
            ['label' => 'Aniversariantes', 'icon' => 'cake-candles', 'route' => 'admin.birthday-banners.index', 'permission' => 'birthday-banners.view'],
        ],
    ],
    [
        'group' => 'ADMINISTRAÇÃO',
        'icon' => 'gear',
        'defaultOpen' => true,
        'items' => [
            ['label' => 'Aparência', 'icon' => 'palette', 'route' => 'admin.appearance.edit', 'permission' => 'appearance.view'],
            ['label' => 'Usuários', 'icon' => 'users', 'route' => 'admin.users.index', 'permission' => 'users.view'],
            ['label' => 'Papéis & Permissões', 'icon' => 'shield-halved', 'route' => 'admin.roles.index', 'permission' => 'roles.manage'],
            ['label' => 'Histórico de Atividades', 'icon' => 'clock-rotate-left', 'route' => 'admin.activity-log.index', 'permission' => 'activity-log.view'],
        ],
    ],
];
