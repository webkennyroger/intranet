{{--
Tela de gestão do Conselho Superior (admin): processos SEI do conselho
(busca, criar, editar, publicar/despublicar, excluir).
--}}
<div class="space-y-6">
    <!-- Cabeçalho da Página -->
    <x-admin.page-header icon="user-tie" title="Conselho Superior"
        subtitle="Gerencie os processos exibidos na página do Conselho Superior." :breadcrumbs="[['label' => 'Painel', 'href' => route('dashboard')], ['label' => 'Conselho Superior']]" />

    <div class="space-y-4">
        <!-- Estatísticas (Cards) -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <x-admin.trend-stat-card label="Total de processos" :value="$totalProcesses" :changePercent="$totalProcessesTrend['changePercent']" :values="$totalProcessesTrend['values']" icon="folder-open" color="#2563EB" />
            <x-admin.trend-stat-card label="Publicados" :value="$publishedProcesses" :changePercent="$publishedProcessesTrend['changePercent']" :values="$publishedProcessesTrend['values']" icon="check" color="#059669" />
            <x-admin.trend-stat-card label="Rascunhos" :value="$draftProcesses" :changePercent="$draftProcessesTrend['changePercent']" :values="$draftProcessesTrend['values']" icon="pen" color="#F59E0B" />
        </div>

        <!-- Busca -->
        <div class="ui-card p-5">
            <div class="flex flex-col md:flex-row md:items-center gap-3">
                <div class="relative flex-1">
                    <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-sm"
                        style="color: var(--text-muted);"></i>
                    <input type="text" wire:model.live.debounce.300ms="processSearch"
                        placeholder="Buscar por número, assunto, relator ou interessado..."
                        class="w-full rounded-full pl-11 pr-4 py-2.5 text-sm placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-(--focus-ring)/40 transition"
                        style="background-color: var(--bg-elevated); border: 1px solid var(--border-color); color: var(--text-primary);" />
                </div>
                <button type="button" wire:click="$refresh" class="inline-flex items-center justify-center gap-2 rounded-full px-6 py-2.5 text-sm font-bold text-white shadow-sm hover:brightness-110 active:brightness-95 transition shrink-0" style="background-color: var(--admin-primary, var(--brand-primary, #0C4A34));">
                    <i class="fa-solid fa-magnifying-glass text-xs"></i>
                    <span>Buscar</span>
                </button>
            </div>
            @if ($processSearch !== '')
                <div class="flex items-center justify-between text-xs mt-3" style="color: var(--text-muted);">
                    <span>{{ $this->processes->total() }} {{ $this->processes->total() === 1 ? 'resultado' : 'resultados' }}</span>
                    <button type="button" wire:click="$set('processSearch', '')" class="font-semibold hover:underline"
                        style="color: var(--admin-primary, var(--brand-primary, #0C4A34));">Limpar filtro</button>
                </div>
            @endif
        </div>

        {{-- Tabela de Processos — isolada num island: selecionar linhas,
             paginar e publicar/excluir só atualizam esta região. --}}
        @island(name: 'council-processes-list', always: true)
        <div class="space-y-4">
            <div class="flex items-center justify-end gap-2">
                @if (count($this->selectedProcesses) > 0)
                    <span class="text-xs font-semibold" style="color: var(--text-muted);">{{ count($this->selectedProcesses) }}
                        selecionado(s)</span>
                    @can('council-processes.update')
                        <x-ui.button type="button" wire:click="bulkPublish" variant="secondary" size="sm">
                            <i class="fa-solid fa-check mr-1"></i> Publicar
                        </x-ui.button>
                        <x-ui.button type="button" wire:click="bulkUnpublish" variant="secondary" size="sm">
                            <i class="fa-solid fa-ban mr-1"></i> Despublicar
                        </x-ui.button>
                    @endcan
                    @can('council-processes.delete')
                        <x-ui.button type="button"
                            @click="confirmAction('Confirma a exclusão de {{ count($this->selectedProcesses) }} processo(s) selecionado(s)?', () => $wire.bulkDeleteProcesses())"
                            variant="danger" size="sm">
                            <i class="fa-solid fa-trash mr-1"></i> Excluir
                        </x-ui.button>
                    @endcan
                    <x-ui.button type="button" wire:click="deselectAllProcesses" variant="ghost"
                        size="sm">Cancelar</x-ui.button>
                @else
                    <x-ui.button wire:click="closeProcessForm"
                        x-on:click="window.dispatchEvent(new CustomEvent('open-modal', { detail: 'council-process-form' }))">
                        Novo processo
                    </x-ui.button>
                @endif
            </div>

            @if ($this->processes->isEmpty())
                <x-ui.empty-state title="Nenhum processo cadastrado ainda." icon="folder" />
            @else
                <x-ui.data-table selectable>
                    <x-slot:header>
                        <th class="px-4 py-3 w-8"><x-ui.table-checkbox header wire:click="toggleSelectAllProcessesOnPage"
                                :checked="$this->allProcessesSelectedOnPage" /></th>
                        <th class="px-6 py-3"><i class="fa-solid fa-hashtag mr-1 opacity-60"></i> Processo SEI</th>
                        <th class="px-6 py-3"><i class="fa-solid fa-file-lines mr-1 opacity-60"></i> Assunto</th>
                        <th class="px-6 py-3"><i class="fa-solid fa-user mr-1 opacity-60"></i> Relator/Atribuído</th>
                        <th class="px-6 py-3"><i class="fa-solid fa-user-group mr-1 opacity-60"></i> Interessado</th>
                        <th class="px-6 py-3"><i class="fa-solid fa-circle-check mr-1 opacity-60"></i> Status</th>
                        <th class="px-6 py-3"><i class="fa-solid fa-bolt mr-1 opacity-60"></i> Ações</th>
                    </x-slot:header>

                    @foreach ($this->processes as $process)
                    <tr wire:key="council-process-{{ $process->id }}"
                        class="odd:bg-white even:bg-slate-50/70 dark:odd:bg-transparent dark:even:bg-(--bg-elevated)/40">
                        <td class="px-4 py-3"><x-ui.table-checkbox wire:model.live="selectedProcesses"
                                value="{{ $process->id }}" /></td>
                        <td class="px-6 py-3 whitespace-nowrap">
                            @if ($process->external_url)
                                <a href="{{ $process->external_url }}" target="_blank"
                                    class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1 text-xs font-bold transition hover:brightness-95"
                                    style="background-color: var(--admin-primary-light, var(--brand-primary-light, #0C4A341a)); color: var(--admin-primary-dark, var(--brand-primary, #0C4A34));">
                                    {{ $process->sei_number }}
                                    <i class="fa-solid fa-arrow-up-right-from-square text-[9px] opacity-70"></i>
                                </a>
                            @else
                                <span class="inline-flex items-center rounded-lg px-2.5 py-1 text-xs font-bold"
                                    style="background-color: var(--bg-elevated); color: var(--text-secondary);">
                                    {{ $process->sei_number }}
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-3" style="color: var(--text-secondary);">{{ $process->subject }}</td>
                        <td class="px-6 py-3" style="color: var(--text-secondary);">{{ $process->assigned_to }}</td>
                        <td class="px-6 py-3" style="color: var(--text-secondary);">{{ $process->interested_party }}</td>
                        <td class="px-6 py-3"><x-ui.badge :variant="$process->is_published ? 'success' : 'warning'">{{ $process->is_published ? 'Publicado' : 'Rascunho' }}</x-ui.badge></td>
                        <td class="px-6 py-3 space-x-2 whitespace-nowrap">
                            <x-ui.tooltip text="Editar">
                                <button wire:click="editProcess({{ $process->id }})"
                                    x-on:click="window.dispatchEvent(new CustomEvent('open-modal', { detail: 'council-process-form' }))"
                                    class="w-9 h-9 inline-flex items-center justify-center rounded transition hover:brightness-95"
                                    style="background-color: rgba(37, 99, 235, 0.1); color: #2563EB;">
                                    <i class="fa-solid fa-pen"></i>
                                </button>
                            </x-ui.tooltip>
                            @can('council-processes.update')
                                <x-ui.tooltip :text="$process->is_published ? 'Despublicar' : 'Publicar'">
                                    <button wire:click="togglePublish({{ $process->id }})"
                                        class="w-9 h-9 inline-flex items-center justify-center rounded transition hover:brightness-95"
                                        style="background-color: rgba(217, 119, 6, 0.1); color: #D97706;">
                                        <i class="fa-solid fa-{{ $process->is_published ? 'toggle-off' : 'toggle-on' }}"></i>
                                    </button>
                                </x-ui.tooltip>
                            @endcan
                            @can('council-processes.delete')
                                <x-ui.tooltip text="Excluir">
                                    <button
                                        @click="confirmAction('Confirma a exclusão deste processo?', () => $wire.deleteProcess({{ $process->id }}))"
                                        class="w-9 h-9 inline-flex items-center justify-center rounded transition hover:brightness-95"
                                        style="background-color: rgba(220, 38, 38, 0.1); color: #DC2626;">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </x-ui.tooltip>
                            @endcan
                        </td>
                    </tr>
                @endforeach

                    <x-slot:footer>
                        {{ $this->processes->links() }}
                    </x-slot:footer>
                </x-ui.data-table>
            @endif
        </div>
        @endisland

        <!-- Modal: Formulário de Processo -->
        <x-ui.modal name="council-process-form" maxWidth="lg">
            <x-slot:header>Processo do Conselho</x-slot:header>
            <livewire:admin.council.council-process-form :process="$editingProcess"
                :key="'council-process-form-' . ($editingProcessId ?? 'new')" />
        </x-ui.modal>
    </div>
</div>