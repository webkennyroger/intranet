{{--
    Tela de login da Intranet.
    Mostra o formulário de e-mail/senha e exibe os erros de validação
    ou de credenciais inválidas vindos do componente Livewire.
--}}
<div class="min-h-screen flex items-center justify-center px-4 py-16" style="background-color: var(--bg-base);">
    <div class="w-full max-w-md">
        <div class="text-center mb-10">
            <h1 class="text-2xl font-bold" style="color: var(--text-primary);">Intranet DPEMT</h1>
            <p class="text-sm mt-1" style="color: var(--text-secondary);">Defensoria Pública do Estado de Mato Grosso</p>
        </div>

        <div class="rounded-3xl p-8 shadow-2xl" style="background-color: var(--bg-card); border: 1px solid var(--border-color);">
            <form wire:submit="submit" class="space-y-5">
                @if ($errors->has('email'))
                    <div class="px-4 py-3 rounded-xl bg-red-500/10 border border-red-500/30 text-red-400 text-sm">
                        {{ $errors->first('email') }}
                    </div>
                @endif

                <x-ui.input wire:model="email" id="email" type="email" autocomplete="email" label="E-mail" />
                <x-ui.input wire:model="password" id="password" type="password" autocomplete="current-password" label="Senha" />
                <x-ui.checkbox label="Manter conectado" wire:model="remember" id="remember" />

                <x-ui.button type="submit" loading="{{ false }}" class="w-full" wire:loading.attr="disabled">
                    <span wire:loading.remove>Entrar</span>
                    <span wire:loading>Autenticando...</span>
                </x-ui.button>
            </form>
        </div>
    </div>
</div>
