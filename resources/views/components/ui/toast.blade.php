@props([
    'position' => 'bottom-right',
    'duration' => 4000,
])

@php
    // Posição do container fixo na tela.
    $positionClasses = match($position) {
        'top-right' => 'top-4 right-4',
        'top-left' => 'top-4 left-4',
        'bottom-left' => 'bottom-4 left-4',
        default => 'bottom-4 right-4', // bottom-right
    };
@endphp

{{--
    Componente global de notificações temporárias ("toasts").

    Inclua UMA VEZ no layout admin (ex: antes do </body> de layouts/admin.blade.php):

        <x-ui.toast />

    Como disparar um toast de qualquer lugar do projeto:

    1) Via JavaScript/Alpine puro, em qualquer template:

        <button x-on:click="window.dispatchEvent(new CustomEvent('toast', {
            detail: { message: 'Salvo com sucesso!', variant: 'success' }
        }))">Salvar</button>

    2) Via Livewire (PHP), dentro de um método de um componente Livewire:

        $this->dispatch('toast', message: 'Salvo com sucesso!', variant: 'success');

       O Livewire despacha isso como um evento de navegador chamado "toast" cujo
       "detail" é exatamente { message: ..., variant: ... }, então o listener
       x-on:toast.window abaixo já entende sem nenhum código extra.

    Variantes de "variant": 'info' (padrão), 'success', 'danger', 'warning'.
    Pode passar também "duration" (ms) no detail para sobrescrever o tempo padrão
    de auto-remoção deste toast específico (ex: duration: 8000 ou 0 para não remover
    automaticamente).
--}}
<div
    x-data="{
        toasts: [],
        add(detail) {
            const id = Date.now() + Math.random();
            const toast = {
                id,
                message: detail?.message ?? '',
                variant: detail?.variant ?? 'info',
                duration: detail?.duration ?? {{ (int) $duration }},
            };
            this.toasts.push(toast);
            if (toast.duration > 0) {
                setTimeout(() => this.remove(id), toast.duration);
            }
        },
        remove(id) {
            this.toasts = this.toasts.filter(t => t.id !== id);
        },
    }"
    x-on:toast.window="add($event.detail)"
    class="fixed {{ $positionClasses }} z-50 flex flex-col gap-2 w-full max-w-sm pointer-events-none"
    {{ $attributes }}
>
    <template x-for="toast in toasts" :key="toast.id">
        <div
            x-show="true"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-2"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="pointer-events-auto flex items-start gap-3 rounded-xl shadow-xl px-4 py-3"
            style="background-color: var(--bg-surface); border: 1px solid var(--border-color); color: var(--text-primary);"
            role="alert"
        >
            <span x-show="toast.variant === 'success'" class="shrink-0 text-emerald-400">
                <x-ui.icon name="check-circle" class="w-5 h-5" />
            </span>
            <span x-show="toast.variant === 'danger'" class="shrink-0 text-red-400">
                <x-ui.icon name="x-circle" class="w-5 h-5" />
            </span>
            <span x-show="toast.variant === 'warning'" class="shrink-0 text-amber-400">
                <x-ui.icon name="exclamation-triangle" class="w-5 h-5" />
            </span>
            <span x-show="toast.variant === 'info'" class="shrink-0 text-blue-400">
                <x-ui.icon name="information-circle" class="w-5 h-5" />
            </span>

            <p class="flex-1 text-sm" x-text="toast.message" style="color: var(--text-primary);"></p>

            <button
                type="button"
                @click="remove(toast.id)"
                class="shrink-0 p-1 rounded-lg transition-colors"
                style="color: var(--text-secondary);"
                onmouseover="this.style.color='var(--text-primary)'"
                onmouseout="this.style.color='var(--text-secondary)'"
                aria-label="Fechar notificação"
            >
                <x-ui.icon name="x-mark" class="w-4 h-4" />
            </button>
        </div>
    </template>
</div>
