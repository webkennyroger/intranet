{{--
Tela de gestão do Conselho Superior (admin): processos SEI do conselho
(busca, criar, editar, publicar/despublicar, excluir). Os membros exibidos
na página pública não têm cadastro aqui — são os usuários com o papel
"Defensor", atribuído em Usuários & Acessos.
--}}
<div class="space-y-6">
    <!-- Cabeçalho da Página -->
    <x-admin.page-header icon="user-tie" title="Conselho Superior"
        subtitle="Gerencie os processos exibidos na página do Conselho Superior." :breadcrumbs="[['label' => 'Painel', 'href' => route('dashboard')], ['label' => 'Conselho Superior']]">
        <x-slot:actions>
            <x-ui.button wire:click="closeProcessForm"
                x-on:click="window.dispatchEvent(new CustomEvent('open-modal', { detail: 'council-process-form' }))">
                Novo processo
            </x-ui.button>
        </x-slot:actions>
    </x-admin.page-header>

    <div class="space-y-4">
        <!-- Toolbar: total de processos (compacto, não é mais um card de
             dashboard inteiro) + busca na mesma barra — em vez de dois
             blocos grandes empilhados (grid de estatística + card de busca
             enorme), os dois cabem numa linha só, enxuta. -->
        <div class="flex flex-col sm:flex-row items-stretch gap-3">
            <div class="ui-card px-4 py-2.5 flex items-center gap-3 shrink-0" style="border-top: 2px solid #2563EB;">
                <span class="w-9 h-9 rounded-lg flex items-center justify-center shrink-0" style="background-color: color-mix(in srgb, #2563EB 14%, transparent);">
                    <i class="fa-solid fa-folder-open text-sm" style="color: #2563EB;"></i>
                </span>
                <div class="whitespace-nowrap">
                    <p class="text-[10px] font-bold uppercase tracking-wide leading-none" style="color: var(--text-muted);">Total de processos</p>
                    <p class="text-lg font-black leading-tight" style="color: var(--text-primary);">{{ $totalProcesses }}</p>
                </div>
            </div>

            <div class="ui-card px-4 py-2.5 flex items-center gap-2.5 flex-1">
                <i class="fa-solid fa-magnifying-glass text-xs shrink-0" style="color: var(--text-muted);"></i>
                <input type="text" wire:model.live.debounce.300ms="processSearch"
                    placeholder="Buscar por número, assunto, relator ou interessado..."
                    class="flex-1 min-w-0 bg-transparent text-sm focus:outline-none"
                    style="color: var(--text-primary);" />
                @if ($processSearch !== '')
                    <span class="text-xs shrink-0" style="color: var(--text-muted);">{{ $this->processes->total() }} {{ $this->processes->total() === 1 ? 'resultado' : 'resultados' }}</span>
                    <button type="button" wire:click="$set('processSearch', '')" class="text-xs font-semibold shrink-0 hover:underline"
                        style="color: var(--admin-primary, var(--brand-primary, #0C4A34));">Limpar</button>
                @endif
            </div>
        </div>

        {{-- Tabela de Processos — isolada num island: selecionar linhas,
             paginar e publicar/excluir só atualizam esta região. --}}
        @island(name: 'council-processes-list', always: true)
        <div class="space-y-4">
            {{-- Barra de ações em massa: só aparece com algo selecionado. Fica
                 aqui dentro do island (não no cabeçalho) porque selecionar
                 uma linha só atualiza esta região — fora do island, o
                 contador/botões ficariam presos no estado inicial. --}}
            @if (count($this->selectedProcesses) > 0)
                <div class="flex items-center justify-end gap-2">
                    <span class="text-xs font-semibold" style="color: var(--text-muted);">{{ count($this->selectedProcesses) }}
                        selecionado(s)</span>
                    @can('council.update')
                        <x-ui.button type="button" wire:click="bulkPublish" variant="secondary" size="sm">
                            <i class="fa-solid fa-check mr-1"></i> Publicar
                        </x-ui.button>
                        <x-ui.button type="button" wire:click="bulkUnpublish" variant="secondary" size="sm">
                            <i class="fa-solid fa-ban mr-1"></i> Despublicar
                        </x-ui.button>
                    @endcan
                    @can('council.delete')
                        <x-ui.button type="button"
                            @click="confirmAction('Confirma a exclusão de {{ count($this->selectedProcesses) }} processo(s) selecionado(s)?', () => $wire.bulkDeleteProcesses())"
                            variant="danger" size="sm">
                            <i class="fa-solid fa-trash mr-1"></i> Excluir
                        </x-ui.button>
                    @endcan
                    <x-ui.button type="button" wire:click="deselectAllProcesses" variant="ghost"
                        size="sm">Cancelar</x-ui.button>
                </div>
            @endif

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
                            @can('council.update')
                                <x-ui.tooltip :text="$process->is_published ? 'Despublicar' : 'Publicar'">
                                    <button wire:click="togglePublish({{ $process->id }})"
                                        class="w-9 h-9 inline-flex items-center justify-center rounded transition hover:brightness-95"
                                        style="background-color: rgba(217, 119, 6, 0.1); color: #D97706;">
                                        <i class="fa-solid fa-{{ $process->is_published ? 'toggle-off' : 'toggle-on' }}"></i>
                                    </button>
                                </x-ui.tooltip>
                            @endcan
                            @can('council.delete')
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
            <x-slot:header>
                <div class="flex items-center gap-3">
                    <span class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0" style="background: linear-gradient(135deg, var(--admin-primary, var(--brand-primary, #0C4A34)), color-mix(in srgb, var(--admin-primary, var(--brand-primary, #0C4A34)) 70%, black));">
                        <i class="fa-solid fa-folder-open text-white"></i>
                    </span>
                    <div>
                        <span class="text-[10px] font-bold uppercase tracking-wide" style="color: var(--admin-primary, var(--brand-primary, #0C4A34));">Conselho Superior</span>
                        <p class="font-black text-base leading-snug" style="color: var(--text-primary);">{{ $editingProcess ? 'Editar processo' : 'Novo processo' }}</p>
                    </div>
                </div>
            </x-slot:header>
            <livewire:admin.council.council-process-form :process="$editingProcess"
                :key="'council-process-form-' . ($editingProcessId ?? 'new')" />
        </x-ui.modal>
    </div>
</div>
