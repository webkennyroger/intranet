{{--
    Modal genérico (overlay + painel), muito usado no admin para confirmações
    e formulários em pop-up. Fica escondido por padrão e abre/fecha ouvindo
    eventos de window: dispare `window.dispatchEvent(new CustomEvent('open-modal',
    { detail: 'nome-do-modal' }))` para abrir e o mesmo com "close-modal" para
    fechar — o "name" da prop precisa casar com o "detail" enviado. Também fecha
    com Esc ou clique no backdrop.
--}}
@props([
    'name' => 'modal',
    'maxWidth' => 'md',
])

@php
    $maxWidths = [
        'sm' => 'max-w-sm',
        'md' => 'max-w-md',
        'lg' => 'max-w-lg',
        'xl' => 'max-w-xl',
        '2xl' => 'max-w-2xl',
        '4xl' => 'max-w-4xl',
    ];
    $widthClass = $maxWidths[$maxWidth] ?? $maxWidths['md'];
@endphp

<div
    x-data="{ open: false }"
    x-on:open-modal.window="(Array.isArray($event.detail) ? $event.detail[0] : $event.detail) === '{{ $name }}' && (open = true)"
    x-on:close-modal.window="(Array.isArray($event.detail) ? $event.detail[0] : $event.detail) === '{{ $name }}' && (open = false)"
    x-on:keydown.escape.window="open = false"
    x-show="open"
    x-cloak
    class="fixed inset-0 z-50 overflow-y-auto"
    aria-modal="true"
>
    <!-- Backdrop -->
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0"
        style="background-color: rgba(0, 0, 0, 0.5);"
        @click="open = false"
    ></div>

    <!-- Modal Panel -->
    <div class="relative flex min-h-full items-center justify-center p-4">
        <div
            x-show="open"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="w-full {{ $widthClass }} rounded-2xl shadow-2xl"
            style="background-color: var(--bg-surface); border: 1px solid var(--border-color);"
        >
            @if(isset($header))
                <div class="px-6 py-4 flex items-center justify-between" style="border-bottom: 1px solid var(--border-color);">
                    <div>{{ $header }}</div>
                    <button @click="open = false" class="p-1 rounded-lg transition-colors" style="color: var(--text-secondary);" onmouseover="this.style.color='var(--text-primary)'" onmouseout="this.style.color='var(--text-secondary)'">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            @endif

            <div class="px-6 py-5">
                {{ $slot }}
            </div>

            @if(isset($footer))
                <div class="px-6 py-4 flex items-center justify-end gap-3" style="border-top: 1px solid var(--border-color);">
                    {{ $footer }}
                </div>
            @endif
        </div>
    </div>
</div>
