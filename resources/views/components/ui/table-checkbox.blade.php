{{--
    Checkbox usado nas linhas e no cabeçalho das tabelas do admin
    (data-table). Controlado inteiramente por fora, igual o x-ui.checkbox
    genérico: nas linhas, via wire:model ligado a um array (ex.
    wire:model.live="selected" value="$item->id"); no cabeçalho, via
    "checked" (refletindo se já estão todos marcados) + wire:click numa
    ação que marca/desmarca tudo (ex. toggleSelectAllOnPage) — ver
    PhotoAlbumIndex/ArticleIndex como referência. "header" é só pra
    documentar a intenção no call site; não muda o HTML.

    No cabeçalho, "checked" é só o atributo HTML (@checked), que o
    navegador só aplica na primeira renderização — depois de um clique,
    ele passa a valer a PROPRIEDADE interna do checkbox, que o morph do
    Livewire não força de volta sozinho (ex.: marcar "selecionar todos",
    excluir em massa, a seleção zera no servidor mas o checkbox continua
    visualmente marcado). O x-effect força a propriedade a cada
    re-render, só pro uso de cabeçalho (as linhas já sincronizam certo
    via wire:model).
--}}
@props(['header' => false, 'checked' => false])

<input
    type="checkbox"
    @checked($checked)
    @if ($header)
        x-data
        x-effect="$el.checked = @js($checked)"
    @endif
    {{ $attributes->merge(['class' => 'w-4 h-4 rounded cursor-pointer']) }}
    style="accent-color: var(--admin-primary, #0C4A34);"
>
