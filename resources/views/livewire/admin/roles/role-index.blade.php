{{--
    Tela admin "Papéis & Permissões": grade de cards com os papéis de acesso
    (clique num card pra ver/editar a matriz dele) e, abaixo, a Matriz de
    Permissões do papel selecionado — uma linha por módulo real do portal,
    com um checkbox de visualizar/criar/editar/excluir em cada uma.
--}}
<div class="space-y-4">
    <!-- Cabeçalho da Página -->
    <x-admin.page-header
        icon="shield-halved"
        title="Papéis & Permissões"
        subtitle="Configure o acesso de cada papel aos módulos do portal."
        :breadcrumbs="[['label' => 'Painel', 'href' => route('dashboard')], ['label' => 'Papéis & Permissões']]"
    >
        <x-slot:actions>
            <x-ui.button :href="route('admin.users.index')" variant="secondary">
                <i class="fa-solid fa-address-book mr-1"></i> Diretório de Usuários
            </x-ui.button>
            <x-ui.button wire:click="savePermissions" :disabled="$this->matrixLocked()">
                <i class="fa-solid fa-check mr-1"></i> Salvar Permissões
            </x-ui.button>
        </x-slot:actions>
    </x-admin.page-header>

    @error('delete')
        <div class="text-sm text-red-600 dark:text-red-400">{{ $message }}</div>
    @enderror

    <!-- Grade de Papéis -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        @foreach ($roles as $role)
            @php $isSelected = $selectedRoleId === $role->id; @endphp
            <div
                wire:click="selectRole({{ $role->id }})"
                wire:key="role-{{ $role->id }}"
                role="button"
                tabindex="0"
                class="text-left rounded-2xl p-4 transition-colors cursor-pointer"
                style="background-color: {{ $isSelected ? 'var(--admin-primary-light)' : 'var(--bg-card)' }}; border: 1px solid {{ $isSelected ? 'var(--admin-primary)' : 'var(--border-color)' }};"
            >
                <div class="flex items-start justify-between gap-2">
                    <span class="font-semibold" style="color: var(--text-primary);">{{ $role->name }}</span>
                    <div class="flex items-center gap-2 shrink-0">
                        @if (in_array($role->name, \App\Livewire\Admin\Roles\RoleIndex::BUILT_IN_ROLES, true))
                            <x-ui.badge size="xs">Padrão do sistema</x-ui.badge>
                        @else
                            <x-ui.badge variant="info" size="xs">Personalizado</x-ui.badge>
                        @endif
                    </div>
                </div>

                <p class="text-xs mt-2 line-clamp-2" style="color: var(--text-secondary);">
                    {{ $role->description ?: 'Sem descrição.' }}
                </p>

                <div class="flex items-center justify-between mt-3">
                    <span class="text-xs font-medium" style="color: var(--admin-primary-dark);">
                        {{ $role->users_count }} {{ $role->users_count === 1 ? 'membro atribuído' : 'membros atribuídos' }}
                    </span>

                    @unless (in_array($role->name, \App\Livewire\Admin\Roles\RoleIndex::BUILT_IN_ROLES, true))
                        <div class="flex items-center gap-2" @click.stop>
                            <x-ui.tooltip text="Editar">
                                <button type="button" wire:click="editRole({{ $role->id }})" class="text-xs text-slate-500 hover:text-emerald-700">
                                    <i class="fa-solid fa-pen"></i>
                                </button>
                            </x-ui.tooltip>
                            <x-ui.tooltip text="Excluir">
                                <button
                                    type="button"
                                    @click="confirmAction('Confirma a exclusão do papel \'{{ $role->name }}\'?', () => $wire.deleteRole({{ $role->id }}))"
                                    class="text-xs text-slate-500 hover:text-red-600"
                                >
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </x-ui.tooltip>
                        </div>
                    @endunless
                </div>
            </div>
        @endforeach

        <!-- Card: Novo Papel -->
        <button
            type="button"
            wire:click="newRole"
            class="rounded-2xl p-4 flex flex-col items-center justify-center gap-2 border-2 border-dashed cursor-pointer transition-colors hover:border-(--admin-primary)"
            style="border-color: var(--border-color); color: var(--text-muted); min-height: 112px;"
        >
            <i class="fa-solid fa-plus text-lg"></i>
            <span class="text-sm font-medium">Novo papel</span>
        </button>
    </div>

    <!-- Matriz de Permissões -->
    <x-ui.card :padding="false">
        <x-slot:header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <div class="font-semibold" style="color: var(--text-primary);">
                        Matriz de Permissões para {{ $this->selectedRole()?->name }}
                    </div>
                    <p class="text-xs mt-0.5" style="color: var(--text-muted);">
                        @if ($this->matrixLocked())
                            Este papel sempre tem acesso total a todos os módulos.
                        @else
                            Selecione as capacidades concedidas a este papel.
                        @endif
                    </p>
                </div>

                @unless ($this->matrixLocked())
                    <div class="flex items-center gap-2 shrink-0">
                        <x-ui.button type="button" variant="secondary" size="sm" wire:click="selectAll">Selecionar Tudo</x-ui.button>
                        <x-ui.button type="button" variant="secondary" size="sm" wire:click="deselectAll">Remover Tudo</x-ui.button>
                    </div>
                @endunless
            </div>
        </x-slot:header>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead>
                    <tr class="text-xs font-semibold uppercase tracking-wide" style="background-color: var(--bg-elevated); border-bottom: 1px solid var(--border-color); color: var(--text-secondary);">
                        <th class="px-6 py-3">Módulo / Recurso</th>
                        @foreach ($actionLabels as $action => $label)
                            <th class="px-6 py-3 text-center">{{ $label }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-(--border-color)">
                    @foreach ($modules as $moduleKey => $module)
                        <tr wire:key="module-{{ $moduleKey }}">
                            <td class="px-6 py-3">
                                <div class="flex items-center gap-2.5">
                                    <i class="fa-solid fa-{{ $module['icon'] }} w-4 text-center" style="color: var(--text-muted);"></i>
                                    <span class="font-semibold" style="color: var(--text-primary);">{{ $module['label'] }}</span>
                                </div>
                            </td>
                            @foreach (array_keys($actionLabels) as $action)
                                @php $permission = "{$moduleKey}.{$action}"; @endphp
                                <td class="px-6 py-3 text-center">
                                    <input
                                        type="checkbox"
                                        wire:model="selectedPermissions"
                                        value="{{ $permission }}"
                                        @disabled($this->matrixLocked())
                                        @checked($this->matrixLocked())
                                        class="w-4 h-4 rounded cursor-pointer disabled:cursor-not-allowed"
                                        style="accent-color: var(--admin-primary);"
                                    >
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-ui.card>

    <!-- Modal: Criar/Editar Papel -->
    <div
        x-data="{ open: @entangle('showRoleForm') }"
        x-show="open"
        x-cloak
        class="fixed inset-0 z-50 overflow-y-auto"
        aria-modal="true"
        role="dialog"
    >
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
            wire:click="closeRoleForm"
        ></div>

        <div class="relative flex min-h-full items-center justify-center p-4">
            <div
                x-show="open"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="w-full max-w-md rounded-2xl shadow-2xl p-6"
                style="background-color: var(--bg-surface); border: 1px solid var(--border-color);"
            >
                <div class="font-semibold mb-4" style="color: var(--text-primary);">
                    {{ $editingRoleId ? 'Editar papel' : 'Novo papel' }}
                </div>

                <form wire:submit="saveRole" class="space-y-4">
                    <x-ui.input wire:model="roleName" label="Nome *" placeholder="Ex: Editor de Conteúdo" />
                    <x-ui.input wire:model="roleDescription" label="Descrição" placeholder="Resumo curto do que este papel pode fazer." />

                    <div class="flex items-center gap-3 pt-2">
                        <x-ui.button type="submit" class="flex-1">Salvar</x-ui.button>
                        <x-ui.button type="button" variant="ghost" wire:click="closeRoleForm">Cancelar</x-ui.button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
