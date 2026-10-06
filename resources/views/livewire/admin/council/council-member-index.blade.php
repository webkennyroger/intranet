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
    >
        <x-slot:actions>
            <x-ui.button wire:click="closeForm" x-on:click="window.dispatchEvent(new CustomEvent('open-modal', { detail: 'council-member-form' }))">
                Novo membro
            </x-ui.button>
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
                <th class="px-4 py-3 w-8"><x-ui.table-checkbox header /></th>
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
                    <td class="px-4 py-3"><x-ui.table-checkbox /></td>
                    <td class="px-4 py-3"><x-ui.drag-handle /></td>
                    <td class="px-6 py-3">
                        <div class="flex items-center gap-2.5">
                            <x-ui.avatar :src="$member->photo_path ? \Illuminate\Support\Facades\Storage::url($member->photo_path) : null" :name="$member->name" size="sm" />
                            <span class="font-semibold" style="color: var(--text-primary);">{{ $member->name }}</span>
                        </div>
                    </td>
                    <td class="px-6 py-3">{{ $member->role }}</td>
                    <td class="px-6 py-3"><x-ui.badge :variant="$member->is_active ? 'success' : 'default'">{{ $member->is_active ? 'Ativo' : 'Inativo' }}</x-ui.badge></td>
                    <td class="px-6 py-3 space-x-3 whitespace-nowrap">
                        <x-ui.tooltip text="Editar">
                            <button wire:click="edit({{ $member->id }})" x-on:click="window.dispatchEvent(new CustomEvent('open-modal', { detail: 'council-member-form' }))" class="text-emerald-700">
                                <i class="fa-solid fa-pen"></i>
                            </button>
                        </x-ui.tooltip>
                        <x-ui.tooltip :text="$member->is_active ? 'Desativar' : 'Ativar'">
                            <button wire:click="toggleActive({{ $member->id }})" class="text-amber-700">
                                <i class="fa-solid fa-{{ $member->is_active ? 'toggle-off' : 'toggle-on' }}"></i>
                            </button>
                        </x-ui.tooltip>
                        @can('council.manage')
                            <x-ui.tooltip text="Excluir">
                                <button wire:click="delete({{ $member->id }})" wire:confirm="Confirma a exclusão deste membro?" class="text-red-700">
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
