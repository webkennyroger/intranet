<?php

namespace App\Services;

/**
 * Lista curada de ícones do FontAwesome (variante "solid", sem o prefixo
 * "fa-") sugeridos nos campos onde o admin digita o nome de um ícone à
 * mão (pastas de Documentos/Capacitações, links de Serviços, capa de
 * Artigos) — evita erro de digitação e a necessidade de ir consultar o
 * catálogo do FontAwesome em outra aba.
 */
class IconCatalog
{
    /**
     * @return array<int, string>
     */
    public static function suggestions(): array
    {
        return [
            'folder', 'folder-open', 'file', 'file-lines', 'file-contract', 'file-signature',
            'file-pdf', 'file-word', 'file-excel', 'file-image', 'file-zipper', 'book', 'book-open',
            'newspaper', 'scale-balanced', 'gavel', 'handshake', 'diagram-project', 'chart-line',
            'chart-simple', 'bullseye', 'calendar-check', 'calendar-days', 'clock', 'clipboard',
            'clipboard-list', 'list-check', 'users', 'user', 'user-tie', 'user-group',
            'chalkboard-user', 'graduation-cap', 'screwdriver-wrench', 'microphone', 'bullhorn',
            'comments', 'comment', 'envelope', 'phone', 'bell', 'leaf', 'heart', 'star', 'shield',
            'shield-halved', 'lock', 'key', 'globe', 'house', 'building', 'map', 'map-pin',
            'location-dot', 'link', 'image', 'images', 'video', 'camera', 'photo-film', 'sliders',
            'gear', 'wrench', 'toolbox', 'briefcase', 'cake-candles', 'gift', 'trophy', 'medal',
            'flag', 'tag', 'tags', 'palette', 'paintbrush', 'lightbulb', 'circle-info',
            'circle-question', 'circle-check', 'triangle-exclamation', 'magnifying-glass', 'eye',
            'thumbs-up', 'share-nodes', 'download', 'upload', 'print', 'folder-tree', 'sitemap',
            'network-wired', 'database', 'server', 'laptop', 'mobile', 'desktop',
        ];
    }
}
