{{--
    Tela de moderação do Fórum (admin).
    Lista os tópicos criados pelos servidores com total de respostas e
    permite abrir o tópico no site ou excluí-lo (junto com as respostas).
--}}
<div class="space-y-4">
    <!-- Cabeçalho da Página -->
    <x-admin.page-header
        icon="bullhorn"
        title="Fórum"
        subtitle="Modere os tópicos e respostas criados pelos servidores."
        :breadcrumbs="[['label' => 'Painel', 'href' => route('dashboard')], ['label' => 'Fórum']]"
    >
        <x-slot:actions>
            <x-ui.button href="{{ route('forum.index') }}" variant="secondary">
                Ver no site
            </x-ui.button>
        </x-slot:actions>
    </x-admin.page-header>

    <!-- Estatísticas (Cards) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <x-admin.trend-stat-card label="Total de tópicos" :value="$totalTopics" :changePercent="$totalTopicsTrend['changePercent']" :values="$totalTopicsTrend['values']" icon="comments" color="#2563EB" />
        <x-admin.trend-stat-card label="Total de respostas" :value="$totalReplies" :changePercent="$totalRepliesTrend['changePercent']" :values="$totalRepliesTrend['values']" icon="reply" color="#9333EA" />
    </div>

    <!-- Busca -->
    <div class="ui-card p-5">
        <div class="flex flex-col md:flex-row md:items-center gap-3">
            <div class="relative flex-1">
                <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-sm" style="color: var(--text-muted);"></i>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Buscar por tópico ou autor..."
                    class="w-full rounded-full pl-11 pr-4 py-2.5 text-sm placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-(--focus-ring)/40 transition"
                    style="background-color: var(--bg-elevated); border: 1px solid var(--border-color); color: var(--text-primary);" />
            </div>
            <button type="button" wire:click="$refresh" class="inline-flex items-center justify-center gap-2 rounded-full px-6 py-2.5 text-sm font-bold text-white shadow-sm hover:brightness-110 active:brightness-95 transition shrink-0" style="background-color: var(--admin-primary, var(--brand-primary, #0C4A34));">
                <i class="fa-solid fa-magnifying-glass text-xs"></i>
                <span>Buscar</span>
            </button>
        </div>
    </div>

    {{-- Tabela de Tópicos — isolada num island: selecionar linhas, paginar
         e excluir só atualizam esta região. --}}
    @island(name: 'forum-topics', always: true)
    <div class="space-y-4">
        @if (count($this->selected) > 0)
            <div class="flex items-center justify-end gap-2">
                <span class="text-xs font-semibold" style="color: var(--text-muted);">{{ count($this->selected) }} selecionado(s)</span>
                @can('forum.delete')
                    <x-ui.button type="button" @click="confirmAction('Confirma a exclusão de {{ count($this->selected) }} tópico(s) selecionado(s) e de todas as respostas?', () => $wire.bulkDelete())" variant="danger" size="sm">
                        <i class="fa-solid fa-trash mr-1"></i> Excluir
                    </x-ui.button>
                @endcan
                <x-ui.button type="button" wire:click="deselectAll" variant="ghost" size="sm">Cancelar</x-ui.button>
            </div>
        @endif

        @if ($this->topics->isEmpty())
            <x-ui.empty-state title="Nenhum tópico criado ainda." icon="link" />
        @else
            <x-ui.data-table selectable>
                <x-slot:header>
                    <th class="px-4 py-3 w-8"><x-ui.table-checkbox header wire:click="toggleSelectAllOnPage" :checked="$this->allSelectedOnPage" /></th>
                    <th class="px-6 py-3"><i class="fa-solid fa-bullhorn mr-1 opacity-60"></i> Tópico</th>
                    <th class="px-6 py-3"><i class="fa-solid fa-user mr-1 opacity-60"></i> Autor</th>
                    <th class="px-6 py-3"><i class="fa-solid fa-comments mr-1 opacity-60"></i> Respostas</th>
                    <th class="px-6 py-3"><i class="fa-solid fa-calendar-days mr-1 opacity-60"></i> Criado em</th>
                    <th class="px-6 py-3"><i class="fa-solid fa-bolt mr-1 opacity-60"></i> Ações</th>
                </x-slot:header>

                @foreach ($this->topics as $topic)
                <tr wire:key="forum-topic-{{ $topic->id }}">
                    <td class="px-4 py-3"><x-ui.table-checkbox wire:model.live="selected" value="{{ $topic->id }}" /></td>
                    <td class="px-6 py-3">
                        <a href="{{ route('forum.show', $topic) }}" target="_blank" class="text-emerald-700 hover:underline">{{ $topic->title }}</a>
                    </td>
                    <td class="px-6 py-3">{{ $topic->author->name }}</td>
                    <td class="px-6 py-3">
                        <x-ui.badge size="sm"><i class="fa-regular fa-comment mr-1.5"></i>{{ $topic->replies_count }}</x-ui.badge>
                    </td>
                    <td class="px-6 py-3">{{ $topic->created_at->format('d/m/Y H:i') }}</td>
                    <td class="px-6 py-3">
                        @can('forum.delete')
                            <x-ui.tooltip text="Excluir">
                                <button @click="confirmAction('Confirma a exclusão deste tópico e de todas as respostas?', () => $wire.delete({{ $topic->id }}))" class="w-9 h-9 inline-flex items-center justify-center rounded transition hover:brightness-95" style="background-color: rgba(220, 38, 38, 0.1); color: #DC2626;">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </x-ui.tooltip>
                        @endcan
                    </td>
                </tr>
            @endforeach

                <x-slot:footer>
                    {{ $this->topics->links() }}
                </x-slot:footer>
            </x-ui.data-table>
        @endif
    </div>
    @endisland
</div>
