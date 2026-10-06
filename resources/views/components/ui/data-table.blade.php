{{--
    Tabela padrão usada em praticamente todas as listagens do admin
    (usuários, artigos, serviços etc.). "sortable" liga o drag-and-drop de
    linhas (via Alpine dragDrop($wire, sortMethod), que chama o método
    Livewire indicado em "sortMethod" a cada reordenação, normalmente junto
    com <x-ui.drag-handle>) e "selectable" habilita o estado "selectAll"
    usado pelos checkboxes de seleção em massa nas linhas.
--}}
@props([
    'striped' => true,
    'hoverable' => true,
    'compact' => false,
    'sortable' => false,
    'sortMethod' => 'reorder',
    'selectable' => false,
])

<div
    {{ $attributes->merge(['class' => 'rounded-2xl overflow-hidden']) }}
    style="background-color: var(--bg-card); border: 1px solid var(--border-color);"
    @if ($selectable) x-data="{ selectAll: false }" @endif
>
    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left">
            @if(isset($header))
                <thead>
                    <tr class="text-xs font-semibold uppercase tracking-wide" style="background-color: var(--bg-elevated); border-bottom: 1px solid var(--border-color); color: var(--text-secondary);">
                        {{ $header }}
                    </tr>
                </thead>
            @endif

            <tbody
                class="divide-y divide-(--border-color) {{ $hoverable ? '[&>tr]:transition-colors [&>tr:hover]:bg-(--bg-hover)' : '' }}"
                style="color: var(--text-primary);"
                @if ($sortable) x-data="dragDrop($wire, '{{ $sortMethod }}')" @endif
            >
                {{ $slot }}
            </tbody>
        </table>
    </div>

    @if(isset($footer))
        <div class="px-6 py-4" style="border-top: 1px solid var(--border-color);">
            {{ $footer }}
        </div>
    @endif
</div>
