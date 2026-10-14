{{--
    Tela de gestão de Capacitações (admin).
    Lista os cursos/treinamentos cadastrados com estatísticas e
    permite criar, editar, publicar/despublicar e excluir capacitações.
--}}
<div class="space-y-4">
    <!-- Cabeçalho da Página -->
    <x-admin.page-header
        icon="graduation-cap"
        title="Capacitações"
        subtitle="Gerencie os cursos e treinamentos disponibilizados para os servidores."
        :breadcrumbs="[['label' => 'Painel', 'href' => route('dashboard')], ['label' => 'Capacitações']]"
    >
        <x-slot:actions>
            @if (count($selected) > 0)
                <span class="text-xs font-semibold" style="color: var(--text-muted);">{{ count($selected) }} selecionado(s)</span>
                @can('trainings.update')
                    <x-ui.button type="button" wire:click="bulkPublish" variant="secondary" size="sm">
                        <i class="fa-solid fa-check mr-1"></i> Publicar
                    </x-ui.button>
                    <x-ui.button type="button" wire:click="bulkUnpublish" variant="secondary" size="sm">
                        <i class="fa-solid fa-ban mr-1"></i> Despublicar
                    </x-ui.button>
                @endcan
                @can('trainings.delete')
                    <x-ui.button type="button" @click="confirmAction('Confirma a exclusão de {{ count($selected) }} capacitação(ões) selecionada(s)?', () => $wire.bulkDelete())" variant="danger" size="sm">
                        <i class="fa-solid fa-trash mr-1"></i> Excluir
                    </x-ui.button>
                @endcan
                <x-ui.button type="button" wire:click="deselectAll" variant="ghost" size="sm">Cancelar</x-ui.button>
            @else
                <x-ui.button wire:click="closeForm" x-on:click="window.dispatchEvent(new CustomEvent('open-modal', { detail: 'training-form' }))">
                    Nova capacitação
                </x-ui.button>
            @endif
        </x-slot:actions>
    </x-admin.page-header>

    <!-- Estatísticas (Cards) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <x-admin.stat-card label="Total de capacitações" :value="$totalTrainings" sublabel="Cadastradas" />
        <x-admin.stat-card label="Publicadas" :value="$publishedTrainings" sublabel="Visíveis no site" valueColor="var(--admin-primary-dark)" />
    </div>

    <!-- Tabela de Capacitações -->
    @if ($trainings->isEmpty())
        <x-ui.empty-state title="Nenhuma capacitação cadastrada ainda." icon="link" />
    @else
        <x-ui.data-table selectable>
            <x-slot:header>
                <th class="px-4 py-3 w-8"><x-ui.table-checkbox header wire:click="toggleSelectAllOnPage" :checked="$allSelectedOnPage" /></th>
                <th class="px-6 py-3"><i class="fa-solid fa-graduation-cap mr-1 opacity-60"></i> Título</th>
                <th class="px-6 py-3"><i class="fa-solid fa-clock mr-1 opacity-60"></i> Carga horária</th>
                <th class="px-6 py-3"><i class="fa-solid fa-circle-check mr-1 opacity-60"></i> Status</th>
                <th class="px-6 py-3"><i class="fa-solid fa-bolt mr-1 opacity-60"></i> Ações</th>
            </x-slot:header>

            @foreach ($trainings as $training)
                <tr wire:key="training-{{ $training->id }}">
                    <td class="px-4 py-3"><x-ui.table-checkbox wire:model.live="selected" value="{{ $training->id }}" /></td>
                    <td class="px-6 py-3">
                        <a href="{{ $training->external_url }}" target="_blank" class="text-emerald-700 hover:underline">{{ $training->title }}</a>
                    </td>
                    <td class="px-6 py-3">{{ $training->workload_hours ? $training->workload_hours.'h' : '—' }}</td>
                    <td class="px-6 py-3"><x-ui.badge :variant="$training->is_published ? 'success' : 'warning'">{{ $training->is_published ? 'Publicado' : 'Rascunho' }}</x-ui.badge></td>
                    <td class="px-6 py-3 space-x-2 whitespace-nowrap">
                        <x-ui.tooltip text="Editar">
                            <button wire:click="edit({{ $training->id }})" x-on:click="window.dispatchEvent(new CustomEvent('open-modal', { detail: 'training-form' }))" class="w-9 h-9 inline-flex items-center justify-center rounded transition hover:brightness-95" style="background-color: rgba(37, 99, 235, 0.1); color: #2563EB;">
                                <i class="fa-solid fa-pen"></i>
                            </button>
                        </x-ui.tooltip>
                        @can('trainings.update')
                            <x-ui.tooltip :text="$training->is_published ? 'Despublicar' : 'Publicar'">
                                <button wire:click="togglePublish({{ $training->id }})" class="w-9 h-9 inline-flex items-center justify-center rounded transition hover:brightness-95" style="background-color: rgba(217, 119, 6, 0.1); color: #D97706;">
                                    <i class="fa-solid fa-{{ $training->is_published ? 'toggle-off' : 'toggle-on' }}"></i>
                                </button>
                            </x-ui.tooltip>
                        @endcan
                        @can('trainings.delete')
                            <x-ui.tooltip text="Excluir">
                                <button @click="confirmAction('Confirma a exclusão desta capacitação?', () => $wire.delete({{ $training->id }}))" class="w-9 h-9 inline-flex items-center justify-center rounded transition hover:brightness-95" style="background-color: rgba(220, 38, 38, 0.1); color: #DC2626;">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </x-ui.tooltip>
                        @endcan
                    </td>
                </tr>
            @endforeach

            <x-slot:footer>
                {{ $trainings->links() }}
            </x-slot:footer>
        </x-ui.data-table>
    @endif

    <!-- Modal: Formulário de Capacitação -->
    <x-ui.modal name="training-form" maxWidth="lg">
        <x-slot:header>Capacitação</x-slot:header>
        <livewire:admin.trainings.training-form :training="$editingTraining" :key="'training-form-'.($editingTrainingId ?? 'new')" />
    </x-ui.modal>
</div>
