{{--
    Dica flutuante que aparece ao passar o mouse (ou focar) sobre o conteúdo do
    slot. Usado nos ícones de ação das tabelas do admin (ex.: "Editar",
    "Excluir") para identificar cada botão. Teleportado pro <body> e
    posicionado via getBoundingClientRect do próprio trigger — assim nunca
    fica cortado/coberto por um ancestral com overflow (ex.: a <aside> da
    sidebar, que tem overflow-y-auto) ou com z-index mais baixo que algum
    vizinho (ex.: o <header> do admin, que é "sticky" + z-index e por isso
    cria seu próprio contexto de empilhamento — o z-50 daqui só valeria
    dentro dele, perdendo pra sidebar sempre que a dica se sobrepusesse a ela).
--}}
@props([
    'text' => '',
    'position' => 'top',
])

@php
    $transformClasses = [
        'top' => '-translate-x-1/2 -translate-y-full',
        'bottom' => '-translate-x-1/2',
        'left' => '-translate-x-full -translate-y-1/2',
        'right' => '-translate-y-1/2',
    ];

    $arrowPositions = [
        'top' => 'top-full left-1/2 -translate-x-1/2 -mt-1',
        'bottom' => 'bottom-full left-1/2 -translate-x-1/2 -mb-1',
        'left' => 'left-full top-1/2 -translate-y-1/2 -ml-1',
        'right' => 'right-full top-1/2 -translate-y-1/2 -mr-1',
    ];

    $transformClass = $transformClasses[$position] ?? $transformClasses['top'];
    $arrowClass = $arrowPositions[$position] ?? $arrowPositions['top'];
@endphp

<div
    x-data="{
        show: false,
        top: 0,
        left: 0,
        place() {
            const r = this.$el.getBoundingClientRect();
            switch ('{{ $position }}') {
                case 'bottom':
                    this.top = r.bottom + 8;
                    this.left = r.left + r.width / 2;
                    break;
                case 'left':
                    this.top = r.top + r.height / 2;
                    this.left = r.left - 8;
                    break;
                case 'right':
                    this.top = r.top + r.height / 2;
                    this.left = r.right + 8;
                    break;
                default:
                    this.top = r.top - 8;
                    this.left = r.left + r.width / 2;
            }
        },
        open() { this.place(); this.show = true; },
    }"
    @mouseenter="open()"
    @mouseleave="show = false"
    @focusin="open()"
    @focusout="show = false"
    {{ $attributes->merge(['class' => 'relative inline-flex']) }}
>
    {{ $slot }}

    <template x-teleport="body">
        <div
            x-show="show"
            x-cloak
            x-transition:enter="transition ease-out duration-150"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-100"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            :style="`top: ${top}px; left: ${left}px;`"
            class="fixed z-50 whitespace-nowrap px-2.5 py-1.5 rounded-lg text-xs font-medium shadow-lg pointer-events-none bg-slate-900 text-white dark:bg-white dark:text-slate-900 {{ $transformClass }}"
            role="tooltip"
        >
            {{ $text }}
            <div
                class="absolute w-2 h-2 rotate-45 bg-slate-900 dark:bg-white {{ $arrowClass }}"
            ></div>
        </div>
    </template>
</div>
