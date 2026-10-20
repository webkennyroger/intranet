<?php

namespace App\Livewire\Site\Sos;

use App\Models\Article;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Página SOS DPEMT: lista as matérias publicadas da categoria "sos-dpemt",
 * com busca por palavra-chave, filtro por período, paginação (9 por
 * página) e curtidas — mesmo comportamento do Mural, só filtrando outra
 * categoria.
 */
class SosIndex extends Component
{
    use WithPagination;

    public string $search = '';

    public string $period = '';

    public function updated(string $property): void
    {
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
        $article = $this->sosQuery()->findOrFail($articleId);
        $user = auth()->user();

        if ($article->isLikedBy($user)) {
            $article->likedByUsers()->detach($user->id);
        } else {
            $article->likedByUsers()->attach($user->id);
        }
    }

    private function sosQuery()
    {
        return Article::published()->whereHas('categories', fn ($q) => $q->where('slug', 'sos-dpemt'));
    }

    /**
     * Isolada num island (ver sos-index.blade.php): precisa estar
     * acessível via $this-> porque islands não têm acesso a variáveis de
     * view vindas de fora deles, só a propriedades/métodos do componente.
     */
    #[Computed]
    public function articles()
    {
        $query = $this->sosQuery()->with('categories');

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

        return $query->orderByDesc('published_at')->paginate(9);
    }

    public function render()
    {
        return view('livewire.site.sos.sos-index');
    }
}
