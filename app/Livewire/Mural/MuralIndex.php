<?php

namespace App\Livewire\Mural;

use App\Models\Article;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Feed público do Mural: lista as matérias publicadas da categoria "Mural"
 * (Notícias e SOS DPEMT têm suas próprias páginas), com busca por texto e
 * filtro por período, além de permitir curtir cada artigo direto na listagem.
 */
class MuralIndex extends Component
{
    use WithPagination;

    public string $search = '';

    public string $period = '';

    public function updated(string $property): void
    {
        // Volta para a primeira página sempre que um filtro muda, para não
        // deixar o usuário "preso" numa página que não existe mais no resultado filtrado.
        if (in_array($property, ['search', 'period'], true)) {
            $this->resetPage();
        }
    }

    public function clearFilters(): void
    {
        $this->reset('search', 'period');
    }

    public function toggleLike(int $articleId): void
    {
        $article = $this->muralQuery()->findOrFail($articleId);
        $user = auth()->user();

        if ($article->isLikedBy($user)) {
            $article->likedByUsers()->detach($user->id);
        } else {
            $article->likedByUsers()->attach($user->id);
        }
    }

    private function muralQuery()
    {
        return Article::published()->whereHas('categories', fn ($q) => $q->where('slug', 'mural'));
    }

    public function render()
    {
        $query = $this->muralQuery()->with('categories');

        if ($this->search !== '') {
            $query->where(function ($q) {
                $q->where('title', 'like', '%'.$this->search.'%')
                    ->orWhere('excerpt', 'like', '%'.$this->search.'%');
            });
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
        ]);
    }
}
