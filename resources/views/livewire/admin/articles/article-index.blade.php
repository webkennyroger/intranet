<div class="max-w-7xl mx-auto space-y-4">
    <div class="flex items-center justify-between">
        <h2 class="text-xl font-bold text-slate-800">Mural</h2>
        <div>
            <x-ui.button wire:click="closeForm" x-on:click="window.dispatchEvent(new CustomEvent('open-modal', { detail: 'article-form' }))">
                Novo artigo
            </x-ui.button>
        </div>
    </div>

    @if ($articles->isEmpty())
        <div class="bg-white border border-slate-200 rounded-2xl p-12 text-center text-slate-500">
            Nenhum artigo cadastrado ainda.
        </div>
    @else
        <x-ui.data-table>
            <x-slot:header>
                <th class="px-6 py-3">Título</th>
                <th class="px-6 py-3">Categoria</th>
                <th class="px-6 py-3">Status</th>
                <th class="px-6 py-3">Curtidas</th>
                <th class="px-6 py-3">Ações</th>
            </x-slot:header>

            @foreach ($articles as $article)
                <tr>
                    <td class="px-6 py-3">{{ $article->title }}</td>
                    <td class="px-6 py-3">{{ $article->category?->name ?? '—' }}</td>
                    <td class="px-6 py-3">{{ $article->is_published ? 'Publicado' : 'Rascunho' }}</td>
                    <td class="px-6 py-3">{{ $article->likedByUsers()->count() }}</td>
                    <td class="px-6 py-3 space-x-2 whitespace-nowrap">
                        <button wire:click="edit({{ $article->id }})" x-on:click="window.dispatchEvent(new CustomEvent('open-modal', { detail: 'article-form' }))" class="text-sm text-emerald-700 hover:underline">
                            Editar
                        </button>
                        @can('content.publish')
                            <button wire:click="togglePublish({{ $article->id }})" class="text-sm text-amber-700 hover:underline">
                                {{ $article->is_published ? 'Despublicar' : 'Publicar' }}
                            </button>
                        @endcan
                        @can('content.delete')
                            <button wire:click="delete({{ $article->id }})" wire:confirm="Confirma a exclusão deste artigo?" class="text-sm text-red-700 hover:underline">
                                Excluir
                            </button>
                        @endcan
                    </td>
                </tr>
            @endforeach

            <x-slot:footer>
                {{ $articles->links() }}
            </x-slot:footer>
        </x-ui.data-table>
    @endif

    <x-ui.modal name="article-form" maxWidth="4xl">
        <x-slot:header>Artigo</x-slot:header>
        <livewire:admin.articles.article-form :article="$editingArticle" :key="'article-form-'.($editingArticleId ?? 'new')" />
    </x-ui.modal>
</div>
