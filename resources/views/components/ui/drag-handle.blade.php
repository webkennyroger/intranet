{{--
    Alça de "arrastar" (ícone de grip) usada nas linhas de tabelas com
    reordenação por drag-and-drop (ex.: <x-ui.data-table sortable>). O
    pointerdown/pointerup chama arm()/disarm() do x-data da tabela pai, pra
    o arraste só começar a partir desta alça, não da linha inteira.
--}}
<x-ui.tooltip text="Arraste para reordenar">
    <span
        data-drag-handle
        x-on:pointerdown.stop="arm($event)"
        x-on:pointerup.stop="disarm($event)"
        x-on:pointercancel.stop="disarm($event)"
        {{ $attributes->class('cursor-grab select-none touch-none') }}
        style="color: var(--text-muted);"
        aria-label="Arraste para reordenar"
    >
        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
            <path d="M7 4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Zm0 6a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Zm0 6a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Zm9-12a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Zm0 6a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Zm0 6a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Z"/>
        </svg>
    </span>
</x-ui.tooltip>
