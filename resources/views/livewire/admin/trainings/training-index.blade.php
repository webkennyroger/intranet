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
    >
        <x-slot:actions>
            <x-ui.button wire:click="closeForm" x-on:click="window.dispatchEvent(new CustomEvent('open-modal', { detail: 'training-form' }))">
                Nova capacitação
            </x-ui.button>
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
                <th class="px-4 py-3 w-8"><x-ui.table-checkbox header /></th>
                <th class="px-6 py-3"><i class="fa-solid fa-graduation-cap mr-1 opacity-60"></i> Título</th>
                <th class="px-6 py-3"><i class="fa-solid fa-clock mr-1 opacity-60"></i> Carga horária</th>
                <th class="px-6 py-3"><i class="fa-solid fa-circle-check mr-1 opacity-60"></i> Status</th>
                <th class="px-6 py-3"><i class="fa-solid fa-bolt mr-1 opacity-60"></i> Ações</th>
            </x-slot:header>

            @foreach ($trainings as $training)
                <tr wire:key="training-{{ $training->id }}">
                    <td class="px-4 py-3"><x-ui.table-checkbox /></td>
                    <td class="px-6 py-3">
                        <a href="{{ $training->external_url }}" target="_blank" class="text-emerald-700 hover:underline">{{ $training->title }}</a>
                    </td>
                    <td class="px-6 py-3">{{ $training->workload_hours ? $training->workload_hours.'h' : '—' }}</td>
                    <td class="px-6 py-3"><x-ui.badge :variant="$training->is_published ? 'success' : 'warning'">{{ $training->is_published ? 'Publicado' : 'Rascunho' }}</x-ui.badge></td>
                    <td class="px-6 py-3 space-x-3 whitespace-nowrap">
                        <x-ui.tooltip text="Editar">
                            <button wire:click="edit({{ $training->id }})" x-on:click="window.dispatchEvent(new CustomEvent('open-modal', { detail: 'training-form' }))" class="text-emerald-700">
                                <i class="fa-solid fa-pen"></i>
                            </button>
                        </x-ui.tooltip>
                        @can('content.publish')
                            <x-ui.tooltip :text="$training->is_published ? 'Despublicar' : 'Publicar'">
                                <button wire:click="togglePublish({{ $training->id }})" class="text-amber-700">
                                    <i class="fa-solid fa-{{ $training->is_published ? 'toggle-off' : 'toggle-on' }}"></i>
                                </button>
                            </x-ui.tooltip>
                        @endcan
                        @can('content.delete')
                            <x-ui.tooltip text="Excluir">
                                <button wire:click="delete({{ $training->id }})" wire:confirm="Confirma a exclusão desta capacitação?" class="text-red-700">
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
