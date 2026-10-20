{{--
    Tela de Usuários & Acessos (admin).
    Lista quem entrou pelo login institucional, permitindo buscar,
    trocar o papel/perfil de cada operador e revogar ou conceder
    acesso (bloquear/desbloquear) e excluir usuários.
--}}
<div class="space-y-4">
    <!-- Cabeçalho da Página -->
    <x-admin.page-header icon="user" title="Usuários & Acessos"
        subtitle="Libere o acesso de quem entrou pelo login institucional e defina o perfil de cada operador."
        :breadcrumbs="[['label' => 'Painel', 'href' => route('dashboard')], ['label' => 'Usuários & Acessos']]" />

    <!-- Busca -->
    <div class="ui-card p-5">
        <div class="flex flex-col md:flex-row md:items-center gap-3">
            <div class="relative flex-1">
                <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-sm" style="color: var(--text-muted);"></i>
                <input type="text" wire:model.live="search" placeholder="Buscar por nome ou e-mail..."
                    class="w-full rounded-full pl-11 pr-4 py-2.5 text-sm placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-(--focus-ring)/40 transition"
                    style="background-color: var(--bg-elevated); border: 1px solid var(--border-color); color: var(--text-primary);" />
            </div>
            <button type="button" wire:click="$refresh" class="inline-flex items-center justify-center gap-2 rounded-full px-6 py-2.5 text-sm font-bold text-white shadow-sm hover:brightness-110 active:brightness-95 transition shrink-0" style="background-color: var(--admin-primary, var(--brand-primary, #0C4A34));">
                <i class="fa-solid fa-magnifying-glass text-xs"></i>
                <span>Buscar</span>
            </button>
        </div>
    </div>

    {{-- Tabela de Usuários — isolada num island: selecionar linhas,
         paginar, bloquear/excluir e trocar papel só atualizam esta
         região. As mensagens de erro ficam dentro porque são geradas por
         ações daqui de dentro (delete/toggleBlock/changeRole). --}}
    @island(name: 'users-list', always: true)
    <div class="space-y-4">
        @if (count($this->selected) > 0)
            <div class="flex items-center justify-end gap-2">
                <span class="text-xs font-semibold" style="color: var(--text-muted);">{{ count($this->selected) }} selecionado(s)</span>
                @can('users.update')
                    <x-ui.button type="button" wire:click="bulkUnblock" variant="secondary" size="sm">
                        <i class="fa-solid fa-lock-open mr-1"></i> Desbloquear
                    </x-ui.button>
                    <x-ui.button type="button" wire:click="bulkBlock" variant="secondary" size="sm">
                        <i class="fa-solid fa-lock mr-1"></i> Bloquear
                    </x-ui.button>
                @endcan
                @can('users.delete')
                    <x-ui.button type="button" @click="confirmAction('Confirma a exclusão de {{ count($this->selected) }} usuário(s) selecionado(s)?', () => $wire.bulkDelete())" variant="danger" size="sm">
                        <i class="fa-solid fa-trash mr-1"></i> Excluir
                    </x-ui.button>
                @endcan
                <x-ui.button type="button" wire:click="deselectAll" variant="ghost" size="sm">Cancelar</x-ui.button>
            </div>
        @endif

        <!-- Mensagens de Erro -->
        @error('delete')
        <div class="text-red-600 dark:text-red-400 text-sm">{{ $message }}</div> @enderror
        @error('toggleBlock')
        <div class="text-red-600 dark:text-red-400 text-sm">{{ $message }}</div> @enderror
        @error('role')
        <div class="text-red-600 dark:text-red-400 text-sm">{{ $message }}</div> @enderror

        <x-ui.data-table selectable>
            <x-slot:header>
                <th class="px-4 py-3 w-8"><x-ui.table-checkbox header wire:click="toggleSelectAllOnPage" :checked="$this->allSelectedOnPage" /></th>
                <th class="px-6 py-3"><i class="fa-solid fa-user mr-1 opacity-60"></i> Pessoa</th>
                <th class="px-4 py-3"><i class="fa-solid fa-building mr-1 opacity-60"></i> Origem</th>
                <th class="px-4 py-3"><i class="fa-solid fa-clock mr-1 opacity-60"></i> Último acesso</th>
                <th class="px-4 py-3"><i class="fa-solid fa-circle-check mr-1 opacity-60"></i> Perfil</th>
                <th class="px-4 py-3"><i class="fa-solid fa-bolt mr-1 opacity-60"></i> Ações</th>
            </x-slot:header>

            @foreach ($this->users as $user)
            <tr wire:key="user-{{ $user->id }}">
                <td class="px-4 py-3">
                    @if ($user->id !== auth()->id())
                        <x-ui.table-checkbox wire:model.live="selected" value="{{ $user->id }}" />
                    @endif
                </td>
                <td class="px-6 py-3">
                    <button wire:click="edit({{ $user->id }})"
                        x-on:click="window.dispatchEvent(new CustomEvent('open-modal', { detail: 'user-form' }))"
                        class="text-left font-semibold hover:underline" style="color: var(--text-primary);">
                        {{ $user->name }}
                        @if ($user->id === auth()->id())
                            <span class="font-normal text-xs" style="color: var(--text-muted);">(você)</span>
                        @endif
                    </button>
                    <p class="text-xs" style="color: var(--text-muted);">{{ $user->email }}</p>
                </td>
                <td class="px-4 py-3">
                    <x-ui.badge variant="success"><i class="fa-solid fa-building mr-1"></i> Institucional</x-ui.badge>
                </td>
                <td class="px-4 py-3 text-sm" style="color: var(--text-secondary);">
                    @if ($user->last_login_at)
                        <p><i class="fa-solid fa-clock mr-1 opacity-60"></i>{{ $user->last_login_at->locale('pt_BR')->diffForHumans() }}</p>
                        <p class="text-xs" style="color: var(--text-muted);">{{ $user->last_login_at->format('d/m/Y H:i') }}</p>
                    @else
                        —
                    @endif
                </td>
                <td class="px-4 py-3">
                    <select wire:model="roleChoice.{{ $user->id }}" wire:change="changeRole({{ $user->id }})"
                        class="rounded-lg px-3 py-1.5 text-sm"
                        style="background-color: var(--bg-card); border: 1px solid var(--border-color); color: var(--text-primary);">
                        @foreach ($this->roles as $r)
                            <option value="{{ $r->name }}">{{ $r->name }}</option>
                        @endforeach
                    </select>
                </td>
                <td class="px-4 py-3 space-x-2 whitespace-nowrap">
                    @if ($user->id !== auth()->id())
                        <x-ui.tooltip :text="$user->isActive() ? 'Revogar acesso' : 'Conceder acesso'">
                            <button
                                @if ($user->isActive())
                                    @click="confirmAction(@js('Revogar o acesso de '.$user->name.'?'), () => $wire.toggleBlock({{ $user->id }}))"
                                @else
                                    wire:click="toggleBlock({{ $user->id }})"
                                @endif
                                class="w-9 h-9 inline-flex items-center justify-center rounded transition hover:brightness-95" style="background-color: rgba(217, 119, 6, 0.1); color: #D97706;">
                                <i class="fa-solid fa-{{ $user->isActive() ? 'ban' : 'circle-check' }}"></i>
                            </button>
                        </x-ui.tooltip>
                        <x-ui.tooltip text="Excluir">
                            <button @click="confirmAction('Confirma a exclusão deste usuário?', () => $wire.delete({{ $user->id }}))"
                                class="w-9 h-9 inline-flex items-center justify-center rounded transition hover:brightness-95" style="background-color: rgba(220, 38, 38, 0.1); color: #DC2626;">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </x-ui.tooltip>
                    @endif
                </td>
            </tr>
        @endforeach

            <x-slot:footer>
                {{ $this->users->links() }}
            </x-slot:footer>
        </x-ui.data-table>
    </div>
    @endisland

    <!-- Modal: Formulário de Usuário -->
    <x-ui.modal name="user-form" maxWidth="lg">
        <x-slot:header>Usuário</x-slot:header>
        <livewire:admin.users.user-form :user="$editingUser" :key="'user-form-' . ($editingUserId ?? 'new')" />
    </x-ui.modal>
</div>