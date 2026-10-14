{{--
    Tela de gestão do Conselho Superior (admin).
    Lista os membros cadastrados, com reordenação por arrastar-e-soltar,
    e permite criar, editar, ativar/desativar e excluir membros.
--}}
<div class="space-y-4">
    <!-- Cabeçalho da Página -->
    <x-admin.page-header
        icon="user-tie"
        title="Conselho Superior"
        subtitle="Gerencie os membros exibidos na página do Conselho Superior."
        :breadcrumbs="[['label' => 'Painel', 'href' => route('dashboard')], ['label' => 'Conselho Superior']]"
    >
        <x-slot:actions>
            @if (count($selected) > 0)
                <span class="text-xs font-semibold" style="color: var(--text-muted);">{{ count($selected) }} selecionado(s)</span>
                @can('council.update')
                    <x-ui.button type="button" wire:click="bulkActivate" variant="secondary" size="sm">
                        <i class="fa-solid fa-check mr-1"></i> Ativar
                    </x-ui.button>
                    <x-ui.button type="button" wire:click="bulkDeactivate" variant="secondary" size="sm">
                        <i class="fa-solid fa-ban mr-1"></i> Desativar
                    </x-ui.button>
                @endcan
                @can('council.delete')
                    <x-ui.button type="button" @click="confirmAction('Confirma a exclusão de {{ count($selected) }} membro(s) selecionado(s)?', () => $wire.bulkDelete())" variant="danger" size="sm">
                        <i class="fa-solid fa-trash mr-1"></i> Excluir
                    </x-ui.button>
                @endcan
                <x-ui.button type="button" wire:click="deselectAll" variant="ghost" size="sm">Cancelar</x-ui.button>
            @else
                <x-ui.button wire:click="closeForm" x-on:click="window.dispatchEvent(new CustomEvent('open-modal', { detail: 'council-member-form' }))">
                    Novo membro
                </x-ui.button>
            @endif
        </x-slot:actions>
    </x-admin.page-header>

    <!-- Estatísticas (Cards) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <x-admin.stat-card label="Total de membros" :value="$totalMembers" sublabel="Cadastrados" />
        <x-admin.stat-card label="Ativos" :value="$activeMembers" sublabel="Visíveis no site" valueColor="var(--admin-primary-dark)" />
    </div>

    <!-- Tabela de Membros (Reordenável) -->
    @if ($members->isEmpty())
        <x-ui.empty-state title="Nenhum membro cadastrado ainda." icon="users" />
    @else
        <x-ui.data-table sortable sortMethod="reorder" selectable>
            <x-slot:header>
                <th class="px-4 py-3 w-8"><x-ui.table-checkbox header wire:click="toggleSelectAllOnPage" :checked="$allSelectedOnPage" /></th>
                <th class="px-4 py-3 w-8"></th>
                <th class="px-6 py-3"><i class="fa-solid fa-user-tie mr-1 opacity-60"></i> Membro</th>
                <th class="px-6 py-3"><i class="fa-solid fa-id-badge mr-1 opacity-60"></i> Cargo</th>
                <th class="px-6 py-3"><i class="fa-solid fa-circle-check mr-1 opacity-60"></i> Status</th>
                <th class="px-6 py-3"><i class="fa-solid fa-bolt mr-1 opacity-60"></i> Ações</th>
            </x-slot:header>

            @foreach ($members as $member)
                <tr
                    wire:key="council-member-{{ $member->id }}"
                    data-sort-id="{{ $member->id }}"
                    draggable="false"
                    x-on:dragstart="start($event)"
                    x-on:dragend="end($event)"
                    x-on:dragover.prevent
                    x-on:drop="drop($event)"
                >
                    <td class="px-4 py-3"><x-ui.table-checkbox wire:model.live="selected" value="{{ $member->id }}" /></td>
                    <td class="px-4 py-3"><x-ui.drag-handle /></td>
                    <td class="px-6 py-3">
                        <div class="flex items-center gap-2.5">
                            <x-ui.avatar :src="$member->photo_path ? \Illuminate\Support\Facades\Storage::url($member->photo_path) : null" :name="$member->name" size="sm" />
                            <span class="font-semibold" style="color: var(--text-primary);">{{ $member->name }}</span>
                        </div>
                    </td>
                    <td class="px-6 py-3">{{ $member->role }}</td>
                    <td class="px-6 py-3"><x-ui.badge :variant="$member->is_active ? 'success' : 'default'">{{ $member->is_active ? 'Ativo' : 'Inativo' }}</x-ui.badge></td>
                    <td class="px-6 py-3 space-x-2 whitespace-nowrap">
                        <x-ui.tooltip text="Editar">
                            <button wire:click="edit({{ $member->id }})" x-on:click="window.dispatchEvent(new CustomEvent('open-modal', { detail: 'council-member-form' }))" class="w-9 h-9 inline-flex items-center justify-center rounded transition hover:brightness-95" style="background-color: rgba(37, 99, 235, 0.1); color: #2563EB;">
                                <i class="fa-solid fa-pen"></i>
                            </button>
                        </x-ui.tooltip>
                        <x-ui.tooltip :text="$member->is_active ? 'Desativar' : 'Ativar'">
                            <button wire:click="toggleActive({{ $member->id }})" class="w-9 h-9 inline-flex items-center justify-center rounded transition hover:brightness-95" style="background-color: rgba(217, 119, 6, 0.1); color: #D97706;">
                                <i class="fa-solid fa-{{ $member->is_active ? 'toggle-off' : 'toggle-on' }}"></i>
                            </button>
                        </x-ui.tooltip>
                        @can('council.delete')
                            <x-ui.tooltip text="Excluir">
                                <button @click="confirmAction('Confirma a exclusão deste membro?', () => $wire.delete({{ $member->id }}))" class="w-9 h-9 inline-flex items-center justify-center rounded transition hover:brightness-95" style="background-color: rgba(220, 38, 38, 0.1); color: #DC2626;">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </x-ui.tooltip>
                        @endcan
                    </td>
                </tr>
            @endforeach
        </x-ui.data-table>
    @endif

    <!-- Modal: Formulário de Membro -->
    <x-ui.modal name="council-member-form" maxWidth="lg">
        <x-slot:header>Membro do Conselho</x-slot:header>
        <livewire:admin.council.council-member-form :member="$editingMember" :key="'council-member-form-'.($editingMemberId ?? 'new')" />
    </x-ui.modal>
</div>
