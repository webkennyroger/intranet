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
            @if (count($selected) > 0)
                <span class="text-xs font-semibold" style="color: var(--text-muted);">{{ count($selected) }} selecionado(s)</span>
                @can('forum.delete')
                    <x-ui.button type="button" @click="confirmAction('Confirma a exclusão de {{ count($selected) }} tópico(s) selecionado(s) e de todas as respostas?', () => $wire.bulkDelete())" variant="danger" size="sm">
                        <i class="fa-solid fa-trash mr-1"></i> Excluir
                    </x-ui.button>
                @endcan
                <x-ui.button type="button" wire:click="deselectAll" variant="ghost" size="sm">Cancelar</x-ui.button>
            @else
                <x-ui.button href="{{ route('forum.index') }}" variant="secondary">
                    Ver no site
                </x-ui.button>
            @endif
        </x-slot:actions>
    </x-admin.page-header>

    <!-- Estatísticas (Cards) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <x-admin.stat-card label="Total de tópicos" :value="$totalTopics" sublabel="Criados pelos servidores" />
        <x-admin.stat-card label="Total de respostas" :value="$totalReplies" sublabel="Em todos os tópicos" valueColor="var(--admin-primary-dark)" />
    </div>

    <!-- Busca -->
    <input type="text" wire:model.live.debounce.300ms="search" placeholder="Buscar por tópico ou autor..."
        class="w-full rounded-lg px-4 py-2.5 text-sm placeholder:text-slate-400"
        style="background-color: var(--bg-card); border: 1px solid var(--border-color); color: var(--text-primary);" />

    <!-- Tabela de Tópicos -->
    @if ($topics->isEmpty())
        <x-ui.empty-state title="Nenhum tópico criado ainda." icon="link" />
    @else
        <x-ui.data-table selectable>
            <x-slot:header>
                <th class="px-4 py-3 w-8"><x-ui.table-checkbox header wire:click="toggleSelectAllOnPage" :checked="$allSelectedOnPage" /></th>
                <th class="px-6 py-3"><i class="fa-solid fa-bullhorn mr-1 opacity-60"></i> Tópico</th>
                <th class="px-6 py-3"><i class="fa-solid fa-user mr-1 opacity-60"></i> Autor</th>
                <th class="px-6 py-3"><i class="fa-solid fa-comments mr-1 opacity-60"></i> Respostas</th>
                <th class="px-6 py-3"><i class="fa-solid fa-calendar-days mr-1 opacity-60"></i> Criado em</th>
                <th class="px-6 py-3"><i class="fa-solid fa-bolt mr-1 opacity-60"></i> Ações</th>
            </x-slot:header>

            @foreach ($topics as $topic)
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
                {{ $topics->links() }}
            </x-slot:footer>
        </x-ui.data-table>
    @endif
</div>
