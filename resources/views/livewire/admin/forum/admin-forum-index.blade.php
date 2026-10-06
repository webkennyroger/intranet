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
    >
        <x-slot:actions>
            <x-ui.button href="{{ route('forum.index') }}" variant="secondary">
                Ver no site
            </x-ui.button>
        </x-slot:actions>
    </x-admin.page-header>

    <!-- Estatísticas (Cards) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <x-admin.stat-card label="Total de tópicos" :value="$totalTopics" sublabel="Criados pelos servidores" />
        <x-admin.stat-card label="Total de respostas" :value="$totalReplies" sublabel="Em todos os tópicos" valueColor="var(--admin-primary-dark)" />
    </div>

    <!-- Tabela de Tópicos -->
    @if ($topics->isEmpty())
        <x-ui.empty-state title="Nenhum tópico criado ainda." icon="link" />
    @else
        <x-ui.data-table selectable>
            <x-slot:header>
                <th class="px-4 py-3 w-8"><x-ui.table-checkbox header /></th>
                <th class="px-6 py-3"><i class="fa-solid fa-bullhorn mr-1 opacity-60"></i> Tópico</th>
                <th class="px-6 py-3"><i class="fa-solid fa-user mr-1 opacity-60"></i> Autor</th>
                <th class="px-6 py-3"><i class="fa-solid fa-comments mr-1 opacity-60"></i> Respostas</th>
                <th class="px-6 py-3"><i class="fa-solid fa-calendar-days mr-1 opacity-60"></i> Criado em</th>
                <th class="px-6 py-3"><i class="fa-solid fa-bolt mr-1 opacity-60"></i> Ações</th>
            </x-slot:header>

            @foreach ($topics as $topic)
                <tr wire:key="forum-topic-{{ $topic->id }}">
                    <td class="px-4 py-3"><x-ui.table-checkbox /></td>
                    <td class="px-6 py-3">
                        <a href="{{ route('forum.show', $topic) }}" target="_blank" class="text-emerald-700 hover:underline">{{ $topic->title }}</a>
                    </td>
                    <td class="px-6 py-3">{{ $topic->author->name }}</td>
                    <td class="px-6 py-3">{{ $topic->replies_count }}</td>
                    <td class="px-6 py-3">{{ $topic->created_at->format('d/m/Y H:i') }}</td>
                    <td class="px-6 py-3">
                        <x-ui.tooltip text="Excluir">
                            <button wire:click="delete({{ $topic->id }})" wire:confirm="Confirma a exclusão deste tópico e de todas as respostas?" class="text-red-700">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </x-ui.tooltip>
                    </td>
                </tr>
            @endforeach

            <x-slot:footer>
                {{ $topics->links() }}
            </x-slot:footer>
        </x-ui.data-table>
    @endif
</div>
