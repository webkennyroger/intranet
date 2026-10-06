<?php

namespace App\Livewire\Admin\Articles;

use App\Models\Article;
use App\Services\ActivityLogger;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Tela admin "Gerenciar Mural": lista os artigos publicados/rascunho,
 * permitindo editar, publicar/despublicar e excluir cada um.
 */
class ArticleIndex extends Component
{
    use WithPagination;

    public ?int $editingArticleId = null;

    #[On('article-saved')]
    public function refreshList(): void
    {
        $this->editingArticleId = null;
    }

    public function edit(int $articleId): void
    {
        $this->editingArticleId = $articleId;
    }

    public function closeForm(): void
    {
        $this->editingArticleId = null;
    }

    public function togglePublish(int $articleId): void
    {
        abort_unless(auth()->user()->can('content.publish'), 403);

        $article = Article::findOrFail($articleId);

        if ($article->is_published) {
            $article->update(['is_published' => false]);
            ActivityLogger::deactivated($article);
        } else {
            $article->update([
                'is_published' => true,
                'published_at' => $article->published_at ?? now(),
            ]);
            ActivityLogger::activated($article);
        }
    }

    public function delete(int $articleId): void
    {
        abort_unless(auth()->user()->can('content.delete'), 403);

        $article = Article::findOrFail($articleId);
        $article->delete();
        ActivityLogger::deleted($article);
    }

    public function render()
    {
        return view('livewire.admin.articles.article-index', [
            'articles' => Article::with(['category', 'author'])->latest()->paginate(10),
            'editingArticle' => $this->editingArticleId ? Article::find($this->editingArticleId) : null,
            'totalArticles' => Article::count(),
            'publishedArticles' => Article::published()->count(),
            'draftArticles' => Article::where('is_published', false)->count(),
            'totalLikes' => DB::table('article_likes')->count(),
        ])->layout('layouts.admin', ['title' => 'Gerenciar Mural']);
    }
}
