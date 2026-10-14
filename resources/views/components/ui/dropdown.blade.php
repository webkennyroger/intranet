{{--
    Menu suspenso genérico: o slot "trigger" é o elemento clicável e o
    slot padrão é o conteúdo do menu que aparece/desaparece ao clicar.
    O menu é teleportado pro final do <body> (x-teleport) e posicionado via
    getBoundingClientRect do próprio trigger — assim ele nunca fica cortado
    por um ancestral com overflow (ex.: o wrapper "overflow-x-auto" de
    x-ui.data-table, que corta qualquer coisa posicionada normalmente
    dentro da tabela).
--}}
@props([
    'align' => 'right',
    'width' => '48',
])

@php
    $widthClass = match ($width) {
        '48' => 'w-48',
        '56' => 'w-56',
        '64' => 'w-64',
        '80' => 'w-80',
        default => 'w-48',
    };
@endphp

<div
    x-data="{
        open: false,
        top: 0,
        left: 0,
        toggle() {
            if (this.open) { this.open = false; return; }
            const r = this.$refs.trigger.getBoundingClientRect();
            this.top = r.bottom + 8;
            this.left = {{ $align === 'left' ? 'r.left' : 'r.right' }};
            this.open = true;
        },
        handleOutsideClick(event) {
            if (this.open && !this.$refs.trigger.contains(event.target) && !this.$refs.panel.contains(event.target)) {
                this.open = false;
            }
        },
    }"
    @click.window="handleOutsideClick($event)"
    @keydown.escape.window="open = false"
    @scroll.window="open = false"
    @resize.window="open = false"
    class="inline-block text-left"
    {{ $attributes }}
>
    <!-- Trigger -->
    <div x-ref="trigger" @click="toggle()">
        {{ $trigger ?? '' }}
    </div>

    <!-- Dropdown Content: teleportado pro <body>, posição calculada em px -->
    <template x-teleport="body">
        <div
            x-ref="panel"
            x-show="open"
            x-cloak
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="fixed z-50 {{ $widthClass }} rounded-xl shadow-xl py-1.5"
            :style="`top: ${top}px; left: ${left}px; transform: translateX({{ $align === 'left' ? '0%' : '-100%' }});`"
            style="background-color: var(--bg-surface); border: 1px solid var(--border-color);"
        >
            {{ $slot }}
        </div>
    </template>
</div>
