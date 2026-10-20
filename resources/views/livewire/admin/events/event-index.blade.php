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
        <x-admin.trend-stat-card label="Total de eventos" :value="$totalEvents" :changePercent="$totalEventsTrend['changePercent']" :values="$totalEventsTrend['values']" icon="calendar-days" color="#2563EB" />
        <x-admin.trend-stat-card label="Próximos" :value="$upcomingEvents" :changePercent="$upcomingEventsTrend['changePercent']" :values="$upcomingEventsTrend['values']" icon="clock" color="#059669" />
    </div>

    <!-- Busca -->
    <div class="ui-card p-5">
        <div class="flex flex-col md:flex-row md:items-center gap-3">
            <div class="relative flex-1">
                <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-sm" style="color: var(--text-muted);"></i>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Buscar por título ou local..."
                    class="w-full rounded-full pl-11 pr-4 py-2.5 text-sm placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-(--focus-ring)/40 transition"
                    style="background-color: var(--bg-elevated); border: 1px solid var(--border-color); color: var(--text-primary);" />
            </div>
            <button type="button" wire:click="$refresh" class="inline-flex items-center justify-center gap-2 rounded-full px-6 py-2.5 text-sm font-bold text-white shadow-sm hover:brightness-110 active:brightness-95 transition shrink-0" style="background-color: var(--admin-primary, var(--brand-primary, #0C4A34));">
                <i class="fa-solid fa-magnifying-glass text-xs"></i>
                <span>Buscar</span>
            </button>
        </div>
    </div>

    <!-- Tabela de Eventos -->
    @if ($events->isEmpty())
        <x-ui.empty-state title="Nenhum evento cadastrado ainda." icon="calendar" />
    @else
        @php($canReorder = $search === '')

        @unless ($canReorder)
            <p class="text-xs" style="color: var(--text-muted);"><i class="fa-solid fa-circle-info mr-1"></i> Limpe a busca para reordenar os eventos arrastando.</p>
        @endunless

        <x-ui.data-table :sortable="$canReorder" selectable>
            <x-slot:header>
                @if ($canReorder)
                    <th class="px-4 py-3 w-8"></th>
                @endif
                <th class="px-4 py-3 w-8"><x-ui.table-checkbox header wire:click="toggleSelectAllOnPage" :checked="$allSelectedOnPage" /></th>
                <th class="px-6 py-3"><i class="fa-solid fa-paperclip mr-1 opacity-60"></i> Anexo</th>
                <th class="px-6 py-3"><i class="fa-solid fa-calendar-days mr-1 opacity-60"></i> Título</th>
                <th class="px-6 py-3"><i class="fa-solid fa-location-dot mr-1 opacity-60"></i> Local</th>
                <th class="px-6 py-3"><i class="fa-solid fa-clock mr-1 opacity-60"></i> Data e Horário</th>
                <th class="px-6 py-3"><i class="fa-solid fa-circle-check mr-1 opacity-60"></i> Status</th>
                <th class="px-6 py-3"><i class="fa-solid fa-bolt mr-1 opacity-60"></i> Ações</th>
            </x-slot:header>

            @foreach ($events as $event)
                <tr
                    wire:key="event-{{ $event->id }}"
                    @if ($canReorder)
                        data-sort-id="{{ $event->id }}"
                        draggable="false"
                        x-on:dragstart="start($event)"
                        x-on:dragend="end($event)"
                        x-on:dragover.prevent
                        x-on:drop="drop($event)"
                    @endif
                >
                    @if ($canReorder)
                        <td class="px-4 py-3"><x-ui.drag-handle /></td>
                    @endif
                    <td class="px-4 py-3"><x-ui.table-checkbox wire:model.live="selected" value="{{ $event->id }}" /></td>
                    <td class="px-6 py-3">
                        @if ($event->cover_image)
                            <img src="{{ $event->coverImageUrl() }}" alt="{{ $event->title }}" class="w-12 h-12 rounded-lg object-cover">
                        @elseif ($event->file_path)
                            <span class="w-12 h-12 rounded-lg flex items-center justify-center {{ $event->fileIconClasses()['bg'] }}">
                                <i class="fa-solid fa-{{ $event->fileIcon() }} text-lg {{ $event->fileIconClasses()['text'] }}"></i>
                            </span>
                        @else
                            <span class="w-12 h-12 rounded-lg border-2 border-dashed flex items-center justify-center" style="border-color: var(--border-color); color: var(--text-muted);">
                                <i class="fa-solid fa-xmark"></i>
                            </span>
                        @endif
                    </td>
                    <td class="px-6 py-3">{{ $event->title }}</td>
                    <td class="px-6 py-3">{{ $event->location ?? '—' }}</td>
                    <td class="px-6 py-3">
                        <div class="font-semibold" style="color: var(--text-primary);">{{ $event->starts_at->translatedFormat('d M Y') }}</div>
                        <div class="text-xs flex items-center gap-1 mt-0.5" style="color: var(--text-muted);">
                            <i class="fa-regular fa-clock"></i>
                            {{ $event->starts_at->format('H:i') }}@if ($event->ends_at) - {{ $event->ends_at->format('H:i') }} @endif
                        </div>
                    </td>
                    <td class="px-6 py-3"><x-ui.badge :variant="$event->is_published ? 'success' : 'warning'">{{ $event->is_published ? 'Publicado' : 'Rascunho' }}</x-ui.badge></td>
                    <td class="px-6 py-3 space-x-2 whitespace-nowrap">
                        <x-ui.tooltip text="Ver">
                            <button type="button" x-on:click="window.dispatchEvent(new CustomEvent('open-modal', { detail: 'event-view-{{ $event->id }}' }))" class="w-9 h-9 inline-flex items-center justify-center rounded transition hover:brightness-95" style="background-color: rgba(5, 150, 105, 0.1); color: #059669;">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </x-ui.tooltip>
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
        </x-ui.data-table>

        <!-- Modais: Visualização de cada Evento (fora da tabela — div dentro de tbody é HTML inválido) -->
        @foreach ($events as $event)
            <x-ui.modal :name="'event-view-'.$event->id" maxWidth="2xl">
                <x-slot:header>
                    <p class="font-bold text-base" style="color: var(--text-primary);">{{ $event->title }}</p>
                </x-slot:header>

                @if ($event->coverImageUrl())
                    <img src="{{ $event->coverImageUrl() }}" alt="{{ $event->title }}" class="w-full h-auto rounded-xl mb-4">
                @endif

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div class="flex items-center gap-3 rounded-xl p-3" style="background-color: var(--bg-elevated);">
                        <span class="w-10 h-10 rounded-lg flex items-center justify-center shrink-0 text-white" style="background-color: var(--admin-primary);">
                            <i class="fa-solid fa-calendar-days"></i>
                        </span>
                        <div class="min-w-0">
                            <p class="text-xs" style="color: var(--text-muted);">Data e horário</p>
                            <p class="text-sm font-bold" style="color: var(--text-primary);">
                                {{ $event->starts_at->translatedFormat('d \d\e F') }} · {{ $event->starts_at->format('H:i') }}@if ($event->ends_at) às {{ $event->ends_at->format('H:i') }} @endif
                            </p>
                        </div>
                    </div>
                    @if ($event->location)
                        <div class="flex items-center gap-3 rounded-xl p-3" style="background-color: var(--bg-elevated);">
                            <span class="w-10 h-10 rounded-lg flex items-center justify-center shrink-0 text-white" style="background-color: var(--admin-primary);">
                                <i class="fa-solid fa-location-dot"></i>
                            </span>
                            <div class="min-w-0">
                                <p class="text-xs" style="color: var(--text-muted);">Local</p>
                                <p class="text-sm font-bold" style="color: var(--text-primary);">{{ $event->location }}</p>
                            </div>
                        </div>
                    @endif
                </div>

                @if ($event->description)
                    <p class="text-sm leading-relaxed mt-4" style="color: var(--text-secondary);">{{ $event->description }}</p>
                @endif

                @if ($event->link || $event->file_path)
                    <div class="flex flex-wrap items-center gap-3 mt-4 pt-4 border-t" style="border-color: var(--border-color);">
                        @if ($event->link)
                            <a href="{{ $event->link }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 rounded-xl px-4 py-2 text-xs font-bold text-white transition hover:brightness-110" style="background-color: var(--admin-primary);">
                                <i class="fa-solid fa-pen-to-square"></i> Realizar Inscrição
                            </a>
                        @endif
                        @if ($event->file_path)
                            <a href="{{ $event->fileUrl() }}" target="_blank" class="inline-flex items-center gap-2 rounded-xl px-4 py-2 text-xs font-bold transition hover:brightness-95" style="background-color: var(--bg-elevated); color: var(--text-primary);">
                                <i class="fa-solid fa-{{ $event->fileIcon() }}"></i> Baixar anexo
                            </a>
                        @endif
                    </div>
                @endif
            </x-ui.modal>
        @endforeach
    @endif

    <!-- Modal: Formulário de Evento -->
    <x-ui.modal name="event-form" maxWidth="lg">
        <x-slot:header>Evento</x-slot:header>
        <livewire:admin.events.event-form :event="$editingEvent" :key="'event-form-'.($editingEventId ?? 'new')" />
    </x-ui.modal>
</div>
