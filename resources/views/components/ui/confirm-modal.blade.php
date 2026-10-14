{{--
    Modal de confirmação genérico, incluído uma única vez nos layouts
    (admin e público). Substitui o confirm() nativo do navegador (que o
    Livewire usa por trás de "wire:confirm") em toda ação destrutiva —
    disparado via confirmAction('mensagem', () => $wire.algumMetodo()),
    definido em resources/js/app.js.
--}}
<div
    x-data="{ open: false, message: '', callback: null }"
    x-on:request-confirm.window="open = true; message = $event.detail.message; callback = $event.detail.callback;"
    x-on:keydown.escape.window="open = false; callback = null;"
    x-show="open"
    x-cloak
    class="fixed inset-0 z-[70] overflow-y-auto"
    aria-modal="true"
    role="alertdialog"
>
    <!-- Backdrop -->
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0"
        style="background-color: rgba(0, 0, 0, 0.5);"
        @click="open = false; callback = null;"
    ></div>

    <!-- Painel -->
    <div class="relative flex min-h-full items-center justify-center p-4">
        <div
            x-show="open"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="w-full max-w-sm rounded-2xl shadow-2xl p-6"
            style="background-color: var(--bg-surface); border: 1px solid var(--border-color);"
        >
            <div class="flex items-start gap-3">
                <span class="w-10 h-10 rounded-full flex items-center justify-center shrink-0" style="background-color: rgba(239, 68, 68, 0.1);">
                    <i class="fa-solid fa-triangle-exclamation" style="color: #ef4444;"></i>
                </span>
                <p class="text-sm pt-2" style="color: var(--text-primary);" x-text="message"></p>
            </div>

            <div class="flex items-center justify-end gap-3 mt-5">
                <x-ui.button type="button" variant="ghost" @click="open = false; callback = null;">Cancelar</x-ui.button>
                <x-ui.button type="button" variant="danger" @click="open = false; const run = callback; callback = null; run && run();">Confirmar</x-ui.button>
            </div>
        </div>
    </div>
</div>
