<?php

namespace App\Livewire\Mural;

use App\Models\Article;
use Livewire\Component;

class ArticleShow extends Component
{
    public Article $article;

    public function mount(Article $article): void
    {
        abort_unless($article->is_published, 404);

        $this->article = $article;
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

    public function render()
    {
        return view('livewire.mural.article-show');
    }
}
