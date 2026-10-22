{{--
    Tela do Conselho Superior: processos SEI publicados (número do
    processo abre o link do SEI quando cadastrado).
--}}
<div class="space-y-4">
    <!-- Cabeçalho da página -->
    <x-site.page-header icon="scale-balanced" title="Conselho Superior" subtitle="Processos do Conselho Superior da Defensoria." />

    <!-- Busca + card de total de processos -->
    <div class="flex flex-col md:flex-row gap-4 items-stretch">
        <div class="ui-card p-5 sm:p-6 flex-1">
            <div class="flex flex-col md:flex-row md:items-center gap-3">
                <div class="relative flex-1">
                    <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-sm text-slate-400 dark:text-(--text-muted)"></i>
                    <input
                        type="text"
                        wire:model.live.debounce.400ms="search"
                        placeholder="Buscar por número, assunto, relator ou interessado..."
                        class="w-full rounded-full pl-11 pr-4 py-3 text-sm border border-slate-200 dark:border-(--border-color) bg-slate-50 dark:bg-(--bg-hover) dark:text-(--text-primary) focus:bg-white dark:focus:bg-(--bg-input) focus:outline-none focus:ring-2 focus:ring-(--focus-ring)/40 focus:border-(--focus-ring) transition-all duration-200"
                    />
                </div>
                <button type="button" wire:click="$refresh" class="inline-flex items-center justify-center gap-2 rounded-full px-6 py-3 text-sm font-bold text-white shadow-sm hover:brightness-110 active:brightness-95 transition shrink-0" style="background-color: var(--brand-primary, #0C4A34);">
                    <i class="fa-solid fa-magnifying-glass text-xs"></i>
                    <span>Buscar</span>
                </button>
            </div>
        </div>

        <!-- Total de processos: contagem geral, não some com o filtro da busca. -->
        <div class="ui-card p-5 flex items-center gap-3 md:w-56 shrink-0" style="border-top: 3px solid var(--brand-primary, #0C4A34);">
            <span class="w-11 h-11 rounded-full flex items-center justify-center shrink-0" style="background-color: var(--brand-primary, #0C4A34);">
                <i class="fa-solid fa-folder-open text-white"></i>
            </span>
            <div class="min-w-0">
                <p class="text-xs font-bold uppercase tracking-wide" style="color: var(--text-muted);">Processos</p>
                <p class="text-2xl font-black" style="color: var(--text-primary);">{{ $totalProcesses }}</p>
            </div>
        </div>
    </div>

    @if ($search !== '')
        <div class="ui-card flex items-center justify-between px-4 py-2.5 text-sm text-slate-500 dark:text-(--text-secondary)">
            <span>Filtro aplicado — {{ $this->processes->total() }} {{ $this->processes->total() === 1 ? 'resultado' : 'resultados' }}</span>
            <button type="button" wire:click="clearFilters" class="font-semibold hover:underline" style="color: var(--brand-primary, #0C4A34);">Limpar filtro</button>
        </div>
    @endif

    {{-- Tabela de processos — isolada num island: buscar e paginar só
         atualizam esta região. --}}
    @island(name: 'council-processes', always: true)
    @if ($this->processes->isEmpty())
        <!-- Estado vazio -->
        <div class="ui-card p-12 text-center text-slate-500 dark:text-(--text-secondary)">
            <i class="fa-regular fa-folder-open text-3xl text-slate-300 dark:text-(--text-muted) mb-3"></i>
            <p>{{ $search !== '' ? 'Nenhum processo encontrado para essa busca.' : 'Nenhum processo cadastrado por enquanto.' }}</p>
        </div>
    @else
        <!-- Card com friso superior (referência ao timbre de um documento oficial), em vez
             de pintar a barra inteira do cabeçalho da tabela de verde. -->
        <div class="ui-card overflow-hidden" style="border-top: 3px solid var(--brand-primary, #0C4A34);">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr style="background-color: var(--bg-elevated); border-bottom: 1px solid var(--border-color);">
                            <th class="px-4 py-3 text-left text-[11px] font-bold uppercase tracking-wider whitespace-nowrap" style="color: var(--text-secondary);"><i class="fa-solid fa-hashtag mr-1.5" style="color: var(--brand-primary, #0C4A34);"></i>Processo SEI</th>
                            <th class="px-4 py-3 text-left text-[11px] font-bold uppercase tracking-wider" style="color: var(--text-secondary);"><i class="fa-solid fa-file-lines mr-1.5" style="color: var(--brand-primary, #0C4A34);"></i>Assunto</th>
                            <th class="px-4 py-3 text-left text-[11px] font-bold uppercase tracking-wider whitespace-nowrap" style="color: var(--text-secondary);"><i class="fa-solid fa-user mr-1.5" style="color: var(--brand-primary, #0C4A34);"></i>Relator/Atribuído</th>
                            <th class="px-4 py-3 text-left text-[11px] font-bold uppercase tracking-wider whitespace-nowrap" style="color: var(--text-secondary);"><i class="fa-solid fa-user-group mr-1.5" style="color: var(--brand-primary, #0C4A34);"></i>Interessado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->processes as $process)
                            <tr class="group border-t border-slate-100 dark:border-(--border-color) transition-colors hover:bg-slate-50 dark:hover:bg-(--bg-hover)" wire:key="council-process-{{ $process->id }}">
                                <td class="relative px-4 py-3 whitespace-nowrap">
                                    <span class="absolute left-0 top-0 bottom-0 w-0.5 scale-y-0 group-hover:scale-y-100 transition-transform" style="background-color: var(--brand-primary, #0C4A34);"></span>
                                    @if ($process->external_url)
                                        <a href="{{ $process->external_url }}" target="_blank" class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1 font-mono text-xs font-bold transition hover:brightness-95" style="background-color: var(--brand-primary-light, #0C4A341a); color: var(--brand-primary, #0C4A34);">
                                            {{ $process->sei_number }}
                                            <i class="fa-solid fa-arrow-up-right-from-square text-[9px] opacity-70"></i>
                                        </a>
                                    @else
                                        <span class="inline-flex items-center rounded-lg px-2.5 py-1 font-mono text-xs font-bold" style="background-color: var(--bg-elevated); color: var(--text-secondary);">
                                            {{ $process->sei_number }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3" style="color: var(--text-primary);">{{ $process->subject }}</td>
                                <td class="px-4 py-3" style="color: var(--text-secondary);">{{ $process->assigned_to }}</td>
                                <td class="px-4 py-3" style="color: var(--text-secondary);">{{ $process->interested_party }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Paginação: dentro do próprio card, igual ao padrão do Dashboard admin. -->
            <div class="px-6 py-4" style="border-top: 1px solid var(--border-color);">
                {{ $this->processes->links() }}
            </div>
        </div>
    @endif
    @endisland
</div>
