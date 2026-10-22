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
        <x-slot:actions>
            @if (count($selected) > 0)
                <span class="text-xs font-semibold" style="color: var(--text-muted);">{{ count($selected) }} selecionado(s)</span>
                @can('activity-log.delete')
                    <x-ui.button type="button" @click="confirmAction('Confirma a exclusão de {{ count($selected) }} registro(s) selecionado(s)?', () => $wire.bulkDelete())" variant="danger" size="sm">
                        <i class="fa-solid fa-trash mr-1"></i> Excluir
                    </x-ui.button>
                @endcan
                <x-ui.button type="button" wire:click="deselectAll" variant="ghost" size="sm">Cancelar</x-ui.button>
            @endif

            <!-- Exportar: mesmo botão/dropdown e ícones de arquivo (Excel/PDF)
                 usados no ESDEP, pra manter a mesma linguagem visual entre
                 os dois sistemas da Defensoria. -->
            <x-ui.dropdown align="right" width="56">
                <x-slot:trigger>
                    <button type="button" class="inline-flex h-10 items-center gap-2 rounded-lg px-4 text-sm font-semibold text-white shadow-xs transition hover:brightness-110 active:brightness-95" style="background-color: var(--admin-primary, var(--brand-primary, #0C4A34));">
                        <i class="fa-solid fa-download text-xs"></i>
                        Exportar
                    </button>
                </x-slot:trigger>

                <x-ui.context-item icon="" wire:click="exportExcel">
                    <span class="flex items-center gap-2.5">
                        <x-icons.excel-icon class="h-5 w-5 shrink-0" />
                        Excel (.xlsx)
                    </span>
                </x-ui.context-item>
                <x-ui.context-item icon="" wire:click="exportPdf">
                    <span class="flex items-center gap-2.5">
                        <x-icons.pdf-icon class="h-5 w-5 shrink-0" />
                        PDF (.pdf)
                    </span>
                </x-ui.context-item>
            </x-ui.dropdown>
        </x-slot:actions>
    </x-admin.page-header>

    <!-- Painel único: busca + filtros + abas, dentro do mesmo cartão — sem
         a linha de estatísticas (não existe no print de referência) e sem
         divisórias internas, pra ficar uma superfície só, igual ao print. -->
    <div class="ui-card p-5 space-y-4">
        <div class="flex flex-wrap items-center gap-3">
            <div class="relative flex-1 basis-72">
                <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-xs" style="color: var(--text-muted);"></i>
                <input type="text" wire:model.live.debounce.300ms="search"
                    placeholder="Buscar por descrição ou usuário..."
                    class="w-full rounded-lg pl-9 pr-3 h-10 text-sm focus:outline-none focus:ring-2 focus:ring-(--focus-ring)/40 transition"
                    style="background-color: var(--bg-elevated); border: 1px solid var(--border-color); color: var(--text-primary);" />
            </div>

            <div class="flex-1 basis-40">
                <x-ui.select wire:model.live="typeFilter" placeholder="Todos os tipos">
                    @foreach ($typeOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </x-ui.select>
            </div>

            <div class="flex-1 basis-40">
                <x-ui.select wire:model.live="periodFilter" placeholder="Todo o período">
                    <option value="today">Hoje</option>
                    <option value="week">Esta semana</option>
                    <option value="month">Este mês</option>
                </x-ui.select>
            </div>

            <div class="flex-1 basis-40">
                <x-ui.select wire:model.live="userFilter" placeholder="Todos os usuários">
                    @foreach ($userOptions as $user)
                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                    @endforeach
                </x-ui.select>
            </div>

            <button type="button" x-on:click="window.dispatchEvent(new CustomEvent('open-modal', { detail: 'activity-log-filters' }))" class="inline-flex items-center gap-2 rounded-lg h-10 px-4 text-sm font-semibold text-white shrink-0" style="background-color: #405189;">
                <i class="fa-solid fa-filter text-xs"></i>
                Filtros
                @if ($dateFrom !== '' && $dateTo !== '')
                    <span class="w-2 h-2 rounded-full" style="background-color: #F06548;"></span>
                @endif
            </button>
        </div>

        <div class="mt-2">
            <x-admin.filter-tabs
                :tabs="collect(\App\Livewire\Admin\ActivityLogIndex::ACTIONS)->mapWithKeys(fn ($label, $action) => [$action => ['label' => $label, 'count' => $tabCounts[$action]]])"
                :active="$actionFilter"
                action="setActionFilter"
                :colors="['' => 'var(--admin-primary)', 'created' => '#059669', 'updated' => '#2563EB', 'activated' => '#059669', 'deactivated' => '#D97706', 'deleted' => '#DC2626']"
                :icons="['' => 'list-check', 'created' => 'plus', 'updated' => 'pen', 'activated' => 'toggle-on', 'deactivated' => 'toggle-off', 'deleted' => 'trash']"
            />
        </div>
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
                    <span class="inline-flex items-center px-1.5 py-0.5 text-[10px] font-semibold uppercase" style="border-radius: 4px; background-color: color-mix(in srgb, {{ $log->action_color }} 12%, transparent); color: {{ $log->action_color }};">
                        {{ $log->action_label }}
                    </span>
                </td>
                <td class="px-4 py-3 text-[13px]" style="color: var(--text-primary);">{{ $log->description }}</td>
                <td class="px-4 py-3">
                    <span class="px-1.5 py-0.5 text-[10px] font-semibold uppercase" style="border-radius: 4px; background-color: var(--bg-elevated); color: var(--text-secondary);">
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
                    <p class="text-xs" style="color: var(--text-muted);">{{ $log->created_at->format('H:i:s') }} &middot; {{ $log->created_at->diffForHumans() }}</p>
                </td>
                <td class="px-4 py-3">
                    <x-ui.tooltip text="Ver detalhes">
                        <button wire:click="openDetail({{ $log->id }})" x-on:click="window.dispatchEvent(new CustomEvent('open-modal', { detail: 'activity-log-detail' }))" class="w-8 h-8 rounded-md flex items-center justify-center transition hover:bg-(--bg-hover)" style="color: var(--text-secondary);">
                            <i class="fa-solid fa-eye text-sm"></i>
                        </button>
                    </x-ui.tooltip>
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

    <!-- Modal: Filtros avançados (intervalo de data customizado, além dos
         períodos fixos hoje/semana/mês já disponíveis na barra) -->
    <x-ui.modal name="activity-log-filters" maxWidth="sm">
        <x-slot:header>
            <div class="flex items-center gap-3">
                <span class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0" style="background: linear-gradient(135deg, var(--admin-primary, var(--brand-primary, #0C4A34)), color-mix(in srgb, var(--admin-primary, var(--brand-primary, #0C4A34)) 70%, black));">
                    <i class="fa-solid fa-filter text-white"></i>
                </span>
                <div>
                    <span class="text-[10px] font-bold uppercase tracking-wide" style="color: var(--admin-primary, var(--brand-primary, #0C4A34));">Histórico de Atividades</span>
                    <p class="font-black text-base leading-snug" style="color: var(--text-primary);">Filtros avançados</p>
                </div>
            </div>
        </x-slot:header>

        <form wire:submit="applyDateRange" class="space-y-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide mb-2" style="color: var(--text-muted);">Intervalo de data</p>
                <div class="grid grid-cols-2 gap-3">
                    <x-ui.input type="date" wire:model="dateFrom" label="De" />
                    <x-ui.input type="date" wire:model="dateTo" label="Até" />
                </div>
                <p class="text-xs mt-2" style="color: var(--text-muted);">Filtra pela data em que a ação aconteceu, além (ou no lugar) dos períodos fixos da barra de busca.</p>
            </div>

            <div class="flex justify-between gap-2">
                <x-ui.button type="button" variant="ghost" wire:click="clearDateRange">Limpar</x-ui.button>
                <x-ui.button type="submit">Aplicar</x-ui.button>
            </div>
        </form>
    </x-ui.modal>

    <!-- Modal: Detalhes da Atividade -->
    <x-ui.modal name="activity-log-detail" maxWidth="lg">
        <x-slot:header>
            @if ($selectedLog)
                <div class="flex items-center gap-3">
                    <span class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0" style="background: linear-gradient(135deg, {{ $selectedLog->action_color }}, color-mix(in srgb, {{ $selectedLog->action_color }} 70%, black));">
                        <i class="fa-solid fa-{{ $selectedLog->action_icon }} text-white"></i>
                    </span>
                    <div>
                        <span class="text-[10px] font-bold uppercase tracking-wide" style="color: {{ $selectedLog->action_color }};">{{ $selectedLog->subject_label }}</span>
                        <p class="font-black text-base leading-snug" style="color: var(--text-primary);">{{ $selectedLog->action_label }}</p>
                    </div>
                </div>
            @else
                Detalhes da atividade
            @endif
        </x-slot:header>

        @if ($selectedLog)
            <div class="space-y-4">
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
