{{--
    Tela de gestão do Mural (admin).
    Lista os artigos cadastrados com estatísticas (total, publicados,
    rascunhos, curtidas) e permite criar, editar, publicar/despublicar
    e excluir artigos através do modal de formulário.
--}}
<div class="space-y-4">
    <!-- Cabeçalho da Página -->
    <x-admin.page-header
        icon="newspaper"
        title="Mural"
        subtitle="Gerencie os artigos publicados no mural interno."
    >
        <x-slot:actions>
            <x-ui.button wire:click="closeForm" x-on:click="window.dispatchEvent(new CustomEvent('open-modal', { detail: 'article-form' }))">
                Novo artigo
            </x-ui.button>
        </x-slot:actions>
    </x-admin.page-header>

    <!-- Estatísticas (Cards) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        <x-admin.stat-card label="Total de artigos" :value="$totalArticles" sublabel="No mural" />
        <x-admin.stat-card label="Publicados" :value="$publishedArticles" sublabel="Visíveis para todos" valueColor="var(--admin-primary-dark)" />
        <x-admin.stat-card label="Rascunhos" :value="$draftArticles" sublabel="Ainda não publicados" />
        <x-admin.stat-card label="Curtidas" :value="$totalLikes" sublabel="No total" />
    </div>

    <!-- Tabela de Artigos -->
    @if ($articles->isEmpty())
        <x-ui.empty-state title="Nenhum artigo cadastrado ainda." icon="document" />
    @else
        <x-ui.data-table selectable>
            <x-slot:header>
                <th class="px-4 py-3 w-8"><x-ui.table-checkbox header /></th>
                <th class="px-6 py-3"><i class="fa-solid fa-newspaper mr-1 opacity-60"></i> Título</th>
                <th class="px-6 py-3"><i class="fa-solid fa-tag mr-1 opacity-60"></i> Categoria</th>
                <th class="px-6 py-3"><i class="fa-solid fa-circle-check mr-1 opacity-60"></i> Status</th>
                <th class="px-6 py-3"><i class="fa-solid fa-heart mr-1 opacity-60"></i> Curtidas</th>
                <th class="px-6 py-3"><i class="fa-solid fa-bolt mr-1 opacity-60"></i> Ações</th>
            </x-slot:header>

            @foreach ($articles as $article)
                <tr wire:key="article-{{ $article->id }}">
                    <td class="px-4 py-3"><x-ui.table-checkbox /></td>
                    <td class="px-6 py-3">{{ $article->title }}</td>
                    <td class="px-6 py-3">{{ $article->category?->name ?? '—' }}</td>
                    <td class="px-6 py-3"><x-ui.badge :variant="$article->is_published ? 'success' : 'warning'">{{ $article->is_published ? 'Publicado' : 'Rascunho' }}</x-ui.badge></td>
                    <td class="px-6 py-3">{{ $article->likedByUsers()->count() }}</td>
                    <td class="px-6 py-3 space-x-3 whitespace-nowrap">
                        <x-ui.tooltip text="Editar">
                            <button wire:click="edit({{ $article->id }})" x-on:click="window.dispatchEvent(new CustomEvent('open-modal', { detail: 'article-form' }))" class="text-emerald-700">
                                <i class="fa-solid fa-pen"></i>
                            </button>
                        </x-ui.tooltip>
                        @can('content.publish')
                            <x-ui.tooltip :text="$article->is_published ? 'Despublicar' : 'Publicar'">
                                <button wire:click="togglePublish({{ $article->id }})" class="text-amber-700">
                                    <i class="fa-solid fa-{{ $article->is_published ? 'toggle-off' : 'toggle-on' }}"></i>
                                </button>
                            </x-ui.tooltip>
                        @endcan
                        @can('content.delete')
                            <x-ui.tooltip text="Excluir">
                                <button wire:click="delete({{ $article->id }})" wire:confirm="Confirma a exclusão deste artigo?" class="text-red-700">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </x-ui.tooltip>
                        @endcan
                    </td>
                </tr>
            @endforeach

            <x-slot:footer>
                {{ $articles->links() }}
            </x-slot:footer>
        </x-ui.data-table>
    @endif

    <!-- Modal: Formulário de Artigo -->
    <x-ui.modal name="article-form" maxWidth="4xl">
        <x-slot:header>Artigo</x-slot:header>
        <livewire:admin.articles.article-form :article="$editingArticle" :key="'article-form-'.($editingArticleId ?? 'new')" />
    </x-ui.modal>
</div>
