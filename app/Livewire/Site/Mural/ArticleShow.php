<?php

namespace App\Livewire\Site\Mural;

use App\Models\Article;
use App\Models\Comment;
use Livewire\Attributes\Validate;
use Livewire\Component;

/**
 * Página de detalhe de uma matéria do Mural: exibe o artigo publicado,
 * permite curtir/descurtir e comentar (um nível de resposta).
 */
class ArticleShow extends Component
{
    public Article $article;

    #[Validate('required|string|max:2000')]
    public string $newComment = '';

    public ?int $replyingToId = null;

    #[Validate('required|string|max:2000')]
    public string $newReply = '';

    public function mount(Article $article): void
    {
        abort_unless($article->is_published, 404);

        $article->incrementViews();

        $this->article = $article->load('categories', 'author');
    }

    public function toggleLike(): void
    {
        $user = auth()->user();

        if ($this->article->isLikedBy($user)) {
            $this->article->likedByUsers()->detach($user->id);
        } else {
            $this->article->likedByUsers()->attach($user->id);
        }
    }

    public function addComment(): void
    {
        $this->validateOnly('newComment');

        Comment::create([
            'article_id' => $this->article->id,
            'author_id' => auth()->id(),
            'body' => $this->newComment,
        ]);

        $this->reset('newComment');
    }

    public function startReply(int $commentId): void
    {
        $this->replyingToId = $commentId;
        $this->newReply = '';
    }

    public function cancelReply(): void
    {
        $this->replyingToId = null;
        $this->newReply = '';
    }

    public function addReply(): void
    {
        $this->validateOnly('newReply');

        Comment::create([
            'article_id' => $this->article->id,
            'author_id' => auth()->id(),
            'parent_id' => $this->replyingToId,
            'body' => $this->newReply,
        ]);

        $this->cancelReply();
    }

    /**
     * Autor do comentário ou Super Administrador — mesma regra já usada
     * na moderação de respostas do Fórum.
     */
    private function canModerate(Comment $comment): bool
    {
        return $comment->author_id === auth()->id() || auth()->user()->hasRole('Super Administrador');
    }

    public function deleteComment(int $commentId): void
    {
        $comment = Comment::findOrFail($commentId);

        abort_unless($this->canModerate($comment), 403);

        $comment->delete();
    }

    public function render()
    {
        $relatedArticles = Article::published()
            ->whereHas('categories', fn ($query) => $query->whereIn('id', $this->article->categories->pluck('id')))
            ->where('id', '!=', $this->article->id)
            ->with('categories', 'author')
            ->latest('published_at')
            ->take(4)
            ->get();

        return view('livewire.site.mural.article-show', [
            'comments' => $this->article->comments()->with(['author', 'replies.author'])->get(),
            'relatedArticles' => $relatedArticles,
            'popularTags' => Article::popularTags(),
        ]);
    }
}
