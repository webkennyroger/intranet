{{--
    Tela de Histórico de Atividades (admin).
    Lista o log de auditoria do sistema (ActivityLog), com filtros por
    busca, tipo, período e usuário, abas por tipo de ação e um modal
    de detalhe mostrando o que mudou em cada registro.
--}}
<div class="space-y-4">
    <!-- Cabeçalho da Página -->
    <x-admin.page-header
        icon="clock-rotate-left"
        title="Histórico de Atividades"
        subtitle="Registro completo de todas as ações realizadas no sistema."
        :breadcrumbs="[['label' => 'Painel', 'href' => route('dashboard')], ['label' => 'Histórico de Atividades']]"
    >
        @if (count($selected) > 0)
            <x-slot:actions>
                <span class="text-xs font-semibold" style="color: var(--text-muted);">{{ count($selected) }} selecionado(s)</span>
                @can('activity-log.delete')
                    <x-ui.button type="button" @click="confirmAction('Confirma a exclusão de {{ count($selected) }} registro(s) selecionado(s)?', () => $wire.bulkDelete())" variant="danger" size="sm">
                        <i class="fa-solid fa-trash mr-1"></i> Excluir
                    </x-ui.button>
                @endcan
                <x-ui.button type="button" wire:click="deselectAll" variant="ghost" size="sm">Cancelar</x-ui.button>
            </x-slot:actions>
        @endif
    </x-admin.page-header>

    <!-- Estatísticas (Cards) -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <x-admin.trend-stat-card label="Total de atividades" :value="$stats['total']" :changePercent="$totalTrend['changePercent']" :values="$totalTrend['values']" icon="list-check" color="#0891B2" />
        <x-admin.stat-card label="Hoje" :value="$stats['today']" icon="clock" iconColor="#2563eb" />
        <x-admin.stat-card label="Esta semana" :value="$stats['thisWeek']" icon="calendar-week" iconColor="#9333ea" />
    </div>

    <!-- Filtros e Abas -->
    <div class="rounded-2xl p-5 space-y-4" style="background-color: var(--bg-card); border: 1px solid var(--border-color);">
        <div class="flex flex-wrap items-end gap-4">
            <div class="flex-1 min-w-[12rem]">
                <x-ui.input wire:model.live="search" label="Buscar" placeholder="Buscar por descrição ou usuário..." />
            </div>

            <div class="min-w-[10rem]">
                <x-ui.select wire:model.live="typeFilter" label="Tipo" placeholder="Todos os tipos">
                    @foreach ($typeOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </x-ui.select>
            </div>

            <div class="min-w-[9rem]">
                <x-ui.select wire:model.live="periodFilter" label="Período" placeholder="Todo o período">
                    <option value="today">Hoje</option>
                    <option value="week">Esta semana</option>
                    <option value="month">Este mês</option>
                </x-ui.select>
            </div>

            <div class="min-w-[10rem]">
                <x-ui.select wire:model.live="userFilter" label="Usuário" placeholder="Todos os usuários">
                    @foreach ($userOptions as $user)
                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                    @endforeach
                </x-ui.select>
            </div>

            <x-ui.button type="button" wire:click="$refresh">
                <i class="fa-solid fa-magnifying-glass mr-1"></i> Buscar
            </x-ui.button>
        </div>

        <x-admin.filter-tabs
            :tabs="collect(\App\Livewire\Admin\ActivityLogIndex::ACTIONS)->mapWithKeys(fn ($label, $action) => [$action => ['label' => $label, 'count' => $tabCounts[$action]]])"
            :active="$actionFilter"
            action="setActionFilter"
        />
    </div>

    <!-- Tabela de Atividades -->
    <x-ui.data-table selectable>
        <x-slot:header>
            <th class="px-4 py-3 w-8"><x-ui.table-checkbox header wire:click="toggleSelectAllOnPage" :checked="$allSelectedOnPage" /></th>
            <th class="px-6 py-3"><i class="fa-solid fa-bolt mr-1 opacity-60"></i> Ação</th>
            <th class="px-4 py-3"><i class="fa-solid fa-align-left mr-1 opacity-60"></i> Descrição</th>
            <th class="px-4 py-3"><i class="fa-solid fa-tag mr-1 opacity-60"></i> Tipo</th>
            <th class="px-4 py-3"><i class="fa-solid fa-user mr-1 opacity-60"></i> Usuário</th>
            <th class="px-4 py-3"><i class="fa-solid fa-clock mr-1 opacity-60"></i> Data/Hora</th>
            <th class="px-4 py-3"><i class="fa-solid fa-circle-info mr-1 opacity-60"></i> Detalhes</th>
        </x-slot:header>

        @forelse ($logs as $log)
            <tr wire:key="activity-log-{{ $log->id }}">
                <td class="px-4 py-3"><x-ui.table-checkbox wire:model.live="selected" value="{{ $log->id }}" /></td>
                <td class="px-6 py-3">
                    <x-ui.badge :variant="$log->action_variant">
                        <i class="fa-solid fa-{{ $log->action_icon }} mr-1"></i> {{ $log->action_label }}
                    </x-ui.badge>
                </td>
                <td class="px-4 py-3 text-sm" style="color: var(--text-primary);">{{ $log->description }}</td>
                <td class="px-4 py-3">
                    <span class="rounded-full px-2 py-0.5 text-xs font-medium" style="background-color: var(--bg-elevated); color: var(--text-muted);">
                        {{ $log->subject_label }}
                    </span>
                </td>
                <td class="px-4 py-3">
                    @if ($log->user)
                        <div class="flex items-center gap-2">
                            <x-ui.avatar :name="$log->user->name" size="sm" />
                            <span class="text-sm" style="color: var(--text-primary);">{{ $log->user->name }}</span>
                        </div>
                    @else
                        <span class="text-sm italic" style="color: var(--text-muted);">Sistema</span>
                    @endif
                </td>
                <td class="px-4 py-3 text-sm" style="color: var(--text-secondary);">
                    <p>{{ $log->created_at->format('d/m/Y') }}</p>
                    <p class="text-xs" style="color: var(--text-muted);">{{ $log->created_at->format('H:i:s') }}</p>
                </td>
                <td class="px-4 py-3">
                    <button wire:click="openDetail({{ $log->id }})" x-on:click="window.dispatchEvent(new CustomEvent('open-modal', { detail: 'activity-log-detail' }))" class="rounded-lg px-3 py-1.5 text-xs font-semibold" style="background-color: var(--bg-elevated); color: var(--text-primary);">
                        Ver
                    </button>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="7">
                    <x-ui.empty-state title="Nenhuma atividade encontrada." icon="clock" />
                </td>
            </tr>
        @endforelse

        <x-slot:footer>
            {{ $logs->links() }}
        </x-slot:footer>
    </x-ui.data-table>

    <!-- Modal: Detalhes da Atividade -->
    <x-ui.modal name="activity-log-detail" maxWidth="lg">
        <x-slot:header>Detalhes da atividade</x-slot:header>

        @if ($selectedLog)
            <div class="space-y-4">
                <div>
                    <x-ui.badge :variant="$selectedLog->action_variant">
                        <i class="fa-solid fa-{{ $selectedLog->action_icon }} mr-1"></i> {{ $selectedLog->action_label }}
                    </x-ui.badge>
                </div>

                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide" style="color: var(--text-muted);">Descrição</p>
                    <p class="text-sm mt-1" style="color: var(--text-primary);">{{ $selectedLog->description }}</p>
                </div>

                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide" style="color: var(--text-muted);">Usuário</p>
                    @if ($selectedLog->user)
                        <div class="flex items-center gap-2 mt-1">
                            <x-ui.avatar :name="$selectedLog->user->name" size="sm" />
                            <div>
                                <p class="text-sm font-semibold" style="color: var(--text-primary);">{{ $selectedLog->user->name }}</p>
                                <p class="text-xs" style="color: var(--text-muted);">{{ $selectedLog->user->email }}</p>
                            </div>
                        </div>
                    @else
                        <p class="text-sm italic mt-1" style="color: var(--text-muted);">Ação do sistema</p>
                    @endif
                </div>

                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide" style="color: var(--text-muted);">Data/Hora</p>
                    <p class="text-sm mt-1" style="color: var(--text-primary);">{{ $selectedLog->created_at->format('d/m/Y H:i:s') }}</p>
                </div>

                @if (count($selectedLog->formatted_changes) > 0)
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide" style="color: var(--text-muted);">Alterações</p>
                        <div class="mt-1 space-y-1.5">
                            @foreach ($selectedLog->formatted_changes as $label => $change)
                                <div class="flex items-start gap-2 text-sm">
                                    <span class="shrink-0 rounded-md px-2 py-0.5 text-xs font-semibold" style="background-color: var(--bg-elevated); color: var(--text-secondary);">{{ $label }}</span>
                                    @if (isset($change['old']))
                                        <span class="line-through" style="color: var(--text-muted);">{{ $change['old'] }}</span>
                                        <span style="color: var(--text-muted);">&rarr;</span>
                                        <span style="color: var(--text-primary);">{{ $change['new'] }}</span>
                                    @else
                                        <span style="color: var(--text-primary);">{{ $change['value'] ?? '' }}</span>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        @endif

        <x-slot:footer>
            <x-ui.button wire:click="closeDetail" x-on:click="window.dispatchEvent(new CustomEvent('close-modal', { detail: 'activity-log-detail' }))" variant="secondary">
                Fechar
            </x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
</div>
