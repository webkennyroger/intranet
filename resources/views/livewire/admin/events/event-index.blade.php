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
        :breadcrumbs="[['label' => 'Painel', 'href' => route('dashboard')], ['label' => 'Eventos']]"
    >
        <x-slot:actions>
            @if (count($selected) > 0)
                <span class="text-xs font-semibold" style="color: var(--text-muted);">{{ count($selected) }} selecionado(s)</span>
                @can('events.update')
                    <x-ui.button type="button" wire:click="bulkPublish" variant="secondary" size="sm">
                        <i class="fa-solid fa-check mr-1"></i> Publicar
                    </x-ui.button>
                    <x-ui.button type="button" wire:click="bulkUnpublish" variant="secondary" size="sm">
                        <i class="fa-solid fa-ban mr-1"></i> Despublicar
                    </x-ui.button>
                @endcan
                @can('events.delete')
                    <x-ui.button type="button" @click="confirmAction('Confirma a exclusão de {{ count($selected) }} evento(s) selecionado(s)?', () => $wire.bulkDelete())" variant="danger" size="sm">
                        <i class="fa-solid fa-trash mr-1"></i> Excluir
                    </x-ui.button>
                @endcan
                <x-ui.button type="button" wire:click="deselectAll" variant="ghost" size="sm">Cancelar</x-ui.button>
            @else
                <x-ui.button wire:click="closeForm" x-on:click="window.dispatchEvent(new CustomEvent('open-modal', { detail: 'event-form' }))">
                    Novo evento
                </x-ui.button>
            @endif
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
                <th class="px-4 py-3 w-8"><x-ui.table-checkbox header wire:click="toggleSelectAllOnPage" :checked="$allSelectedOnPage" /></th>
                <th class="px-6 py-3"><i class="fa-solid fa-calendar-days mr-1 opacity-60"></i> Título</th>
                <th class="px-6 py-3"><i class="fa-solid fa-location-dot mr-1 opacity-60"></i> Local</th>
                <th class="px-6 py-3"><i class="fa-solid fa-clock mr-1 opacity-60"></i> Início</th>
                <th class="px-6 py-3"><i class="fa-solid fa-circle-check mr-1 opacity-60"></i> Status</th>
                <th class="px-6 py-3"><i class="fa-solid fa-bolt mr-1 opacity-60"></i> Ações</th>
            </x-slot:header>

            @foreach ($events as $event)
                <tr wire:key="event-{{ $event->id }}">
                    <td class="px-4 py-3"><x-ui.table-checkbox wire:model.live="selected" value="{{ $event->id }}" /></td>
                    <td class="px-6 py-3">{{ $event->title }}</td>
                    <td class="px-6 py-3">{{ $event->location ?? '—' }}</td>
                    <td class="px-6 py-3">{{ $event->starts_at->format('d/m/Y H:i') }}</td>
                    <td class="px-6 py-3"><x-ui.badge :variant="$event->is_published ? 'success' : 'warning'">{{ $event->is_published ? 'Publicado' : 'Rascunho' }}</x-ui.badge></td>
                    <td class="px-6 py-3 space-x-2 whitespace-nowrap">
                        <x-ui.tooltip text="Editar">
                            <button wire:click="edit({{ $event->id }})" x-on:click="window.dispatchEvent(new CustomEvent('open-modal', { detail: 'event-form' }))" class="w-9 h-9 inline-flex items-center justify-center rounded transition hover:brightness-95" style="background-color: rgba(37, 99, 235, 0.1); color: #2563EB;">
                                <i class="fa-solid fa-pen"></i>
                            </button>
                        </x-ui.tooltip>
                        @can('events.update')
                            <x-ui.tooltip :text="$event->is_published ? 'Despublicar' : 'Publicar'">
                                <button wire:click="togglePublish({{ $event->id }})" class="w-9 h-9 inline-flex items-center justify-center rounded transition hover:brightness-95" style="background-color: rgba(217, 119, 6, 0.1); color: #D97706;">
                                    <i class="fa-solid fa-{{ $event->is_published ? 'toggle-off' : 'toggle-on' }}"></i>
                                </button>
                            </x-ui.tooltip>
                        @endcan
                        @can('events.delete')
                            <x-ui.tooltip text="Excluir">
                                <button @click="confirmAction('Confirma a exclusão deste evento?', () => $wire.delete({{ $event->id }}))" class="w-9 h-9 inline-flex items-center justify-center rounded transition hover:brightness-95" style="background-color: rgba(220, 38, 38, 0.1); color: #DC2626;">
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
