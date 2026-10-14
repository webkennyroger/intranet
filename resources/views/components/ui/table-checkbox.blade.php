{{--
    Checkbox usado nas linhas e no cabeçalho das tabelas do admin
    (data-table). Controlado inteiramente por fora, igual o x-ui.checkbox
    genérico: nas linhas, via wire:model ligado a um array (ex.
    wire:model.live="selected" value="$item->id"); no cabeçalho, via
    "checked" (refletindo se já estão todos marcados) + wire:click numa
    ação que marca/desmarca tudo (ex. toggleSelectAllOnPage) — ver
    PhotoAlbumIndex/ArticleIndex como referência. "header" é só pra
    documentar a intenção no call site; não muda o HTML.
--}}
@props(['header' => false, 'checked' => false])

<input
    type="checkbox"
    @checked($checked)
    {{ $attributes->merge(['class' => 'w-4 h-4 rounded cursor-pointer']) }}
    style="accent-color: var(--admin-primary, #0C4A34);"
>
