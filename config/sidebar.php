<?php

/**
 * Itens do menu lateral do site público (área logada do usuário comum).
 *
 * Mesma estrutura do config/admin_sidebar.php — 'label', 'icon', 'uri'
 * (caminho da página), 'route' (nome da rota) e 'permission' (aqui sempre
 * null, pois este menu não restringe por permissão) — mas organizada em
 * grupos (GERAL, CONTEÚDO, GESTÃO, OUTROS) para o usuário final, em vez dos
 * grupos administrativos do menu de admin.
 */
return [
    [
        'label' => 'Início',
        'icon' => 'house',
        'uri' => '/inicio',
        'route' => 'inicio',
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
            ['label' => 'Mural', 'icon' => 'newspaper', 'uri' => '/mural', 'route' => 'mural.index', 'permission' => null],
            ['label' => 'Serviços', 'icon' => 'sliders', 'uri' => '/servicos', 'route' => 'services.index', 'permission' => null],
            ['label' => 'Documentos', 'icon' => 'folder-open', 'uri' => '/documentos', 'route' => 'documents.index', 'permission' => null],
        ],
    ],
    [
        'group' => 'GESTÃO',
        'items' => [
            ['label' => 'Conselho Superior', 'icon' => 'scale-balanced', 'uri' => '/conselho-superior', 'route' => 'council.index', 'permission' => null],
            ['label' => 'Notícias', 'icon' => 'comment-dots', 'uri' => '/noticias', 'route' => 'noticias.index', 'permission' => null],
            ['label' => 'Mídias', 'icon' => 'photo-film', 'uri' => '/fotos', 'route' => 'photos.index', 'permission' => null],
        ],
    ],
    [
        'group' => 'OUTROS',
        'items' => [
            ['label' => 'Eventos', 'icon' => 'calendar-days', 'uri' => '/eventos', 'route' => 'events.index', 'permission' => null],
            ['label' => 'Capacitações', 'icon' => 'graduation-cap', 'uri' => '/capacitacoes', 'route' => 'trainings.index', 'permission' => null],
            ['label' => 'Fórum', 'icon' => 'bullhorn', 'uri' => '/forum', 'route' => 'forum.index', 'permission' => null],
            ['label' => 'SOS DPEMT', 'icon' => 'life-ring', 'uri' => '/sos', 'route' => 'sos.index', 'permission' => null],
            ['label' => 'Aniversariantes', 'icon' => 'cake-candles', 'uri' => '/aniversariantes', 'route' => 'birthdays.index', 'permission' => null],
        ],
    ],
];
