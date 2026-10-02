<?php

return [
    [
        'label' => 'Início',
        'icon' => 'house',
        'uri' => '/dashboard',
        'route' => 'dashboard',
        'permission' => null,
    ],
    [
        'group' => 'GERAL',
        'items' => [
            ['label' => 'Assistente Geral', 'icon' => 'robot', 'uri' => '/assistente-geral', 'route' => 'placeholder.assistente-geral', 'permission' => null],
        ],
    ],
    [
        'group' => 'CONTEÚDO',
        'items' => [
            ['label' => 'Mural', 'icon' => 'newspaper', 'uri' => '/mural', 'route' => 'placeholder.mural', 'permission' => null],
            ['label' => 'Serviços', 'icon' => 'sliders', 'uri' => '/servicos', 'route' => 'services.index', 'permission' => null],
            ['label' => 'Documentos', 'icon' => 'folder-open', 'uri' => '/documentos', 'route' => 'placeholder.documentos', 'permission' => null],
        ],
    ],
    [
        'group' => 'GESTÃO',
        'items' => [
            ['label' => 'Conselho Superior', 'icon' => 'scale-balanced', 'uri' => '/conselho-superior', 'route' => 'placeholder.conselho-superior', 'permission' => null],
            ['label' => 'Notícias', 'icon' => 'comment-dots', 'uri' => '/noticias', 'route' => 'placeholder.noticias', 'permission' => null],
            ['label' => 'Fotos', 'icon' => 'camera', 'uri' => '/fotos', 'route' => 'placeholder.fotos', 'permission' => null],
            ['label' => 'Vídeos', 'icon' => 'video', 'uri' => '/videos', 'route' => 'placeholder.videos', 'permission' => null],
        ],
    ],
    [
        'group' => 'OUTROS',
        'items' => [
            ['label' => 'Eventos', 'icon' => 'calendar-days', 'uri' => '/eventos', 'route' => 'placeholder.eventos', 'permission' => null],
            ['label' => 'Capacitações', 'icon' => 'graduation-cap', 'uri' => '/capacitacoes', 'route' => 'placeholder.capacitacoes', 'permission' => null],
            ['label' => 'Fórum', 'icon' => 'bullhorn', 'uri' => '/forum', 'route' => 'placeholder.forum', 'permission' => null],
            ['label' => 'SOS DPEMT', 'icon' => 'life-ring', 'uri' => '/sos', 'route' => 'placeholder.sos', 'permission' => null],
            ['label' => 'Aniversariantes', 'icon' => 'cake-candles', 'uri' => '/aniversariantes', 'route' => 'placeholder.aniversariantes', 'permission' => null],
        ],
    ],
    [
        'group' => 'ADMINISTRAÇÃO',
        'items' => [
            ['label' => 'Usuários', 'icon' => 'users', 'uri' => '/admin/usuarios', 'route' => 'admin.users.index', 'permission' => 'users.view'],
            ['label' => 'Gerenciar Serviços', 'icon' => 'sliders', 'uri' => '/admin/servicos', 'route' => 'admin.services.index', 'permission' => 'services.manage'],
            ['label' => 'Gerenciar Mural', 'icon' => 'newspaper', 'uri' => '/admin/mural', 'route' => 'admin.articles.index', 'permission' => 'content.view'],
        ],
    ],
];
