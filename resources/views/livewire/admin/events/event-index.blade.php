{{--
    Tela de gestão de Eventos (admin).
    Lista os eventos cadastrados com estatísticas (total e próximos) e
    permite criar, editar, publicar/despublicar e excluir eventos.
--}}
<div class="space-y-4">
    <!-- Cabeçalho da Página -->
    <x-admin.page-header
        icon="calendar-days"
        title="Eventos"
        subtitle="Gerencie os eventos exibidos no site."
    >
        <x-slot:actions>
            <x-ui.button wire:click="closeForm" x-on:click="window.dispatchEvent(new CustomEvent('open-modal', { detail: 'event-form' }))">
                Novo evento
            </x-ui.button>
        </x-slot:actions>
    </x-admin.page-header>

    <!-- Estatísticas (Cards) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <x-admin.stat-card label="Total de eventos" :value="$totalEvents" sublabel="Cadastrados" />
        <x-admin.stat-card label="Próximos" :value="$upcomingEvents" sublabel="Ainda não ocorreram" valueColor="var(--admin-primary-dark)" />
    </div>

    <!-- Tabela de Eventos -->
    @if ($events->isEmpty())
        <x-ui.empty-state title="Nenhum evento cadastrado ainda." icon="calendar" />
    @else
        <x-ui.data-table selectable>
            <x-slot:header>
                <th class="px-4 py-3 w-8"><x-ui.table-checkbox header /></th>
                <th class="px-6 py-3"><i class="fa-solid fa-calendar-days mr-1 opacity-60"></i> Título</th>
                <th class="px-6 py-3"><i class="fa-solid fa-location-dot mr-1 opacity-60"></i> Local</th>
                <th class="px-6 py-3"><i class="fa-solid fa-clock mr-1 opacity-60"></i> Início</th>
                <th class="px-6 py-3"><i class="fa-solid fa-circle-check mr-1 opacity-60"></i> Status</th>
                <th class="px-6 py-3"><i class="fa-solid fa-bolt mr-1 opacity-60"></i> Ações</th>
            </x-slot:header>

            @foreach ($events as $event)
                <tr wire:key="event-{{ $event->id }}">
                    <td class="px-4 py-3"><x-ui.table-checkbox /></td>
                    <td class="px-6 py-3">{{ $event->title }}</td>
                    <td class="px-6 py-3">{{ $event->location ?? '—' }}</td>
                    <td class="px-6 py-3">{{ $event->starts_at->format('d/m/Y H:i') }}</td>
                    <td class="px-6 py-3"><x-ui.badge :variant="$event->is_published ? 'success' : 'warning'">{{ $event->is_published ? 'Publicado' : 'Rascunho' }}</x-ui.badge></td>
                    <td class="px-6 py-3 space-x-3 whitespace-nowrap">
                        <x-ui.tooltip text="Editar">
                            <button wire:click="edit({{ $event->id }})" x-on:click="window.dispatchEvent(new CustomEvent('open-modal', { detail: 'event-form' }))" class="text-emerald-700">
                                <i class="fa-solid fa-pen"></i>
                            </button>
                        </x-ui.tooltip>
                        @can('content.publish')
                            <x-ui.tooltip :text="$event->is_published ? 'Despublicar' : 'Publicar'">
                                <button wire:click="togglePublish({{ $event->id }})" class="text-amber-700">
                                    <i class="fa-solid fa-{{ $event->is_published ? 'toggle-off' : 'toggle-on' }}"></i>
                                </button>
                            </x-ui.tooltip>
                        @endcan
                        @can('content.delete')
                            <x-ui.tooltip text="Excluir">
                                <button wire:click="delete({{ $event->id }})" wire:confirm="Confirma a exclusão deste evento?" class="text-red-700">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </x-ui.tooltip>
                        @endcan
                    </td>
                </tr>
            @endforeach

            <x-slot:footer>
                {{ $events->links() }}
            </x-slot:footer>
        </x-ui.data-table>
    @endif

    <!-- Modal: Formulário de Evento -->
    <x-ui.modal name="event-form" maxWidth="lg">
        <x-slot:header>Evento</x-slot:header>
        <livewire:admin.events.event-form :event="$editingEvent" :key="'event-form-'.($editingEventId ?? 'new')" />
    </x-ui.modal>
</div>
