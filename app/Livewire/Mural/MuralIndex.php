<?php

namespace App\Livewire\Mural;

use App\Models\Article;
use App\Models\Category;
use Livewire\Component;
use Livewire\WithPagination;

class MuralIndex extends Component
{
    use WithPagination;

    public string $search = '';

    public string $categoryId = '';

    public string $period = '';

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'categoryId', 'period'], true)) {
            $this->resetPage();
        }
    }

    public function toggleLike(int $articleId): void
    {
        $article = Article::findOrFail($articleId);
        $user = auth()->user();

        if ($article->isLikedBy($user)) {
            $article->likedByUsers()->detach($user->id);
        } else {
            $article->likedByUsers()->attach($user->id);
        }
    }

    public function render()
    {
        $query = Article::published()->with('category');

        if ($this->search !== '') {
            $query->where(function ($q) {
                $q->where('title', 'like', '%'.$this->search.'%')
                    ->orWhere('excerpt', 'like', '%'.$this->search.'%');
            });
        }

        if ($this->categoryId !== '') {
            $query->where('category_id', $this->categoryId);
        }

        if ($this->period !== '') {
            $from = match ($this->period) {
                '7d' => now()->subDays(7),
                '30d' => now()->subDays(30),
                'year' => now()->startOfYear(),
                default => null,
            };

            if ($from) {
                $query->where('published_at', '>=', $from);
            }
        }

        return view('livewire.mural.mural-index', [
            'articles' => $query->orderByDesc('published_at')->paginate(9),
            'categories' => Category::orderBy('name')->get(),
        ]);
    }
}
