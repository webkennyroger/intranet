<?php

namespace App\Livewire\Admin\Articles;

use App\Models\Article;
use App\Models\Category;
use App\Services\ActivityLogger;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Tela admin "Gerenciar Notícias": lista todos os artigos (Notícias, Mural
 * e SOS DPEMT são a mesma tabela, só filtrada por categoria — "Mural" é só
 * mais uma categoria entre as outras), em formato de cards com busca,
 * filtro por categoria/ano numa barra lateral, e permite publicar/
 * despublicar e excluir cada um. Criar e editar acontecem em páginas
 * próprias (admin.articles.create/edit).
 */
class ArticleIndex extends Component
{
    use WithPagination;

    public string $search = '';

    public string $categoryId = '';

    public string $year = '';

    /** @var array<int, int> */
    public array $selected = [];

    public function updated(string $property): void
    {
        // Volta para a primeira página sempre que um filtro muda, para não
        // deixar o admin "preso" numa página que não existe mais no resultado filtrado.
        if (in_array($property, ['search', 'categoryId', 'year'], true)) {
            $this->resetPage();
            $this->selected = [];
        }
    }

    public function clearFilters(): void
    {
        $this->reset('search', 'categoryId', 'year');
        $this->selected = [];
    }

    public function togglePublish(int $articleId): void
    {
        abort_unless(auth()->user()->can('articles.update'), 403);

        $article = Article::findOrFail($articleId);

        if ($article->is_published) {
            $article->update(['is_published' => false]);
            ActivityLogger::deactivated($article);
            $this->dispatch('toast', title: 'Artigo', message: 'Artigo despublicado.', variant: 'warning');
        } else {
            $article->update([
                'is_published' => true,
                'published_at' => $article->published_at ?? $article->created_at,
            ]);
            ActivityLogger::activated($article);
            $this->dispatch('toast', title: 'Artigo', message: 'Artigo publicado com sucesso.', variant: 'success');
        }
    }

    public function delete(int $articleId): void
    {
        abort_unless(auth()->user()->can('articles.delete'), 403);

        $article = Article::findOrFail($articleId);
        $article->delete();
        ActivityLogger::deleted($article);
        $this->dispatch('toast', title: 'Artigo', message: 'Artigo excluído com sucesso.', variant: 'danger');
    }

    /**
     * IDs da página atual, respeitando os filtros ativos — usado tanto na
     * listagem principal quanto para "selecionar todos nesta página".
     */
    private function currentPageIds(): array
    {
        return $this->filteredQuery()->latest('published_at')->paginate(6)->pluck('id')->all();
    }

    public function toggleSelectAllOnPage(): void
    {
        $pageIds = $this->currentPageIds();

        if (array_diff($pageIds, $this->selected) === []) {
            $this->selected = array_values(array_diff($this->selected, $pageIds));
        } else {
            $this->selected = array_values(array_unique(array_merge($this->selected, $pageIds)));
        }
    }

    public function deselectAll(): void
    {
        $this->selected = [];
    }

    public function bulkPublish(): void
    {
        abort_unless(auth()->user()->can('articles.update'), 403);

        $articles = Article::whereIn('id', $this->selected)->where('is_published', false)->get();

        foreach ($articles as $article) {
            $article->update(['is_published' => true, 'published_at' => $article->published_at ?? $article->created_at]);
            ActivityLogger::activated($article);
        }

        $this->selected = [];
        $message = $articles->isNotEmpty() ? $articles->count().' artigo(s) publicado(s) com sucesso.' : 'Nenhum rascunho selecionado para publicar.';
        $this->dispatch('toast', title: 'Artigos', message: $message, variant: $articles->isNotEmpty() ? 'success' : 'warning');
    }

    public function bulkUnpublish(): void
    {
        abort_unless(auth()->user()->can('articles.update'), 403);

        $articles = Article::whereIn('id', $this->selected)->where('is_published', true)->get();

        foreach ($articles as $article) {
            $article->update(['is_published' => false]);
            ActivityLogger::deactivated($article);
        }

        $this->selected = [];
        $message = $articles->isNotEmpty() ? $articles->count().' artigo(s) despublicado(s).' : 'Nenhum artigo publicado selecionado.';
        $this->dispatch('toast', title: 'Artigos', message: $message, variant: 'warning');
    }

    public function bulkDelete(): void
    {
        abort_unless(auth()->user()->can('articles.delete'), 403);

        $articles = Article::whereIn('id', $this->selected)->get();

        foreach ($articles as $article) {
            $article->delete();
            ActivityLogger::deleted($article);
        }

        $this->selected = [];
        $this->dispatch('toast', title: 'Artigos', message: $articles->count().' artigo(s) excluído(s) com sucesso.', variant: 'danger');
    }

    private function filteredQuery()
    {
        $query = Article::with(['categories', 'author']);

        if ($this->search !== '') {
            $query->where(function ($q) {
                $q->where('title', 'like', '%'.$this->search.'%')
                    ->orWhere('excerpt', 'like', '%'.$this->search.'%');
            });
        }

        if ($this->categoryId !== '') {
            $query->whereHas('categories', fn ($q) => $q->where('categories.id', $this->categoryId));
        }

        if ($this->year !== '') {
            $query->whereYear('published_at', $this->year);
        }

        return $query;
    }

    public function render()
    {
        // Agrupa os anos em PHP (em vez de YEAR()/strftime() no banco) pra
        // não depender de uma função específica do driver SQLite/MySQL.
        $archiveYears = Article::whereNotNull('published_at')
            ->get(['published_at'])
            ->groupBy(fn ($article) => $article->published_at->year)
            ->map->count()
            ->sortKeysDesc();

        $articles = $this->filteredQuery()->latest('published_at')->paginate(6);

        return view('livewire.admin.articles.article-index', [
            'articles' => $articles,
            'allSelectedOnPage' => $articles->isNotEmpty() && array_diff($articles->pluck('id')->all(), $this->selected) === [],
            'totalArticles' => Article::count(),
            'publishedArticles' => Article::published()->count(),
            'draftArticles' => Article::where('is_published', false)->count(),
            'totalLikes' => DB::table('article_likes')->count(),
            'categories' => Category::withCount('articles')->orderBy('name')->get(),
            'archiveYears' => $archiveYears,
            'popularPosts' => Article::published()->withCount('likedByUsers')->orderByDesc('liked_by_users_count')->take(3)->get(),
        ])->layout('layouts.admin', ['title' => 'Gerenciar Notícias']);
    }
}
