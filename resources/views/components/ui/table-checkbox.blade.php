{{--
    Checkbox usado nas linhas e no cabeçalho das tabelas do admin (data-table).
    Com "header" true, controla "selectAll" (marca/desmarca todas as linhas);
    nas linhas, só reflete esse estado. É puramente visual por enquanto — ainda
    não existe nenhuma ação em massa (excluir/editar selecionados) ligada a isso.
--}}
@props(['header' => false])

<input
    type="checkbox"
    @if ($header) x-model="selectAll" @else :checked="selectAll" @endif
    {{ $attributes->merge(['class' => 'w-4 h-4 rounded cursor-pointer']) }}
    style="accent-color: var(--admin-primary, #0C4A34);"
>
