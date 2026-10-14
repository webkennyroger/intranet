{{--
    Notificação padrão de toda ação de criar/editar/excluir/atualizar do admin
    (e das telas públicas com CRUD, como o Fórum). Incluído uma vez em
    layouts/admin.blade.php e layouts/app.blade.php — o comentário mais
    abaixo explica como dispará-lo de qualquer Livewire component.
--}}
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

    Inclua UMA VEZ em cada layout (já incluído em layouts/admin.blade.php e
    layouts/app.blade.php):

        <x-ui.toast />

    Como disparar um toast de qualquer lugar do projeto:

    1) Via JavaScript/Alpine puro, em qualquer template:

        <button x-on:click="window.dispatchEvent(new CustomEvent('toast', {
            detail: { title: 'Usuário', message: 'Usuário atualizado com sucesso.', variant: 'success' }
        }))">Salvar</button>

    2) Via Livewire (PHP), dentro de um método de um componente Livewire:

        $this->dispatch('toast', title: 'Usuário', message: 'Usuário atualizado com sucesso.', variant: 'success');

       O Livewire despacha isso como um evento de navegador chamado "toast" cujo
       "detail" é exatamente { title: ..., message: ..., variant: ... }, então o
       listener x-on:toast.window abaixo já entende sem nenhum código extra.

    "title" é opcional (se omitido, o toast mostra só a mensagem, sem a linha em
    negrito). Variantes de "variant": 'info' (padrão), 'success', 'danger', 'warning'.
    Pode passar também "duration" (ms) no detail para sobrescrever o tempo padrão
    de auto-remoção deste toast específico (ex: duration: 8000 ou 0 para não remover
    automaticamente).

    3) Quando a ação REDIRECIONA (ex.: salvar e voltar pra listagem), um evento
       "toast" disparado antes do redirect pode não sobreviver à navegação. Nesses
       casos, flasheie na sessão a mesma estrutura antes do redirect:

        session()->flash('toast', ['title' => 'Artigo', 'message' => 'Artigo salvo com sucesso.', 'variant' => 'success']);

       Este componente lê `session('toast')` automaticamente ao carregar a página.
--}}
<div
    x-data="{
        toasts: [],
        add(detail) {
            const id = Date.now() + Math.random();
            const toast = {
                id,
                title: detail?.title ?? '',
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
    x-init="@if(session('toast')) add(@js(session('toast'))) @endif"
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
            class="pointer-events-auto flex items-start gap-3 rounded-xl shadow-xl p-4"
            style="background-color: var(--bg-surface); border: 1px solid var(--border-color); color: var(--text-primary);"
            role="alert"
        >
            <span
                class="shrink-0 w-10 h-10 rounded-xl flex items-center justify-center"
                :class="{
                    'bg-emerald-500/10 text-emerald-500': toast.variant === 'success',
                    'bg-red-500/10 text-red-500': toast.variant === 'danger',
                    'bg-amber-500/10 text-amber-500': toast.variant === 'warning',
                    'bg-blue-500/10 text-blue-500': toast.variant === 'info',
                }"
            >
                <x-ui.icon x-show="toast.variant === 'success'" name="check-circle" class="w-5 h-5" />
                <x-ui.icon x-show="toast.variant === 'danger'" name="x-circle" class="w-5 h-5" />
                <x-ui.icon x-show="toast.variant === 'warning'" name="exclamation-triangle" class="w-5 h-5" />
                <x-ui.icon x-show="toast.variant === 'info'" name="information-circle" class="w-5 h-5" />
            </span>

            <div class="flex-1 min-w-0 pt-0.5">
                <p x-show="toast.title" x-text="toast.title" class="text-sm font-semibold" style="color: var(--text-primary);"></p>
                <p x-text="toast.message" class="text-sm mt-0.5" style="color: var(--text-secondary);"></p>
            </div>

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
