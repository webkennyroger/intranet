<?php

namespace App\Livewire\Admin\Articles;

use App\Models\Article;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

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
        } else {
            $article->update([
                'is_published' => true,
                'published_at' => $article->published_at ?? now(),
            ]);
        }
    }

    public function delete(int $articleId): void
    {
        abort_unless(auth()->user()->can('content.delete'), 403);

        Article::findOrFail($articleId)->delete();
    }

    public function render()
    {
        return view('livewire.admin.articles.article-index', [
            'articles' => Article::with(['category', 'author'])->latest()->paginate(15),
            'editingArticle' => $this->editingArticleId ? Article::find($this->editingArticleId) : null,
        ]);
    }
}
