<?php

namespace App\Livewire\Site\Noticias;

use App\Models\Article;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Página pública de Notícias: lista as matérias publicadas que pertencem à
 * categoria "noticia", com busca e arquivo por ano numa barra lateral —
 * mesmo design da tela admin, mas sem nenhuma ação de gestão (editar/
 * excluir/publicar), só leitura.
 */
class NoticiasIndex extends Component
{
    use WithPagination;

    public string $search = '';

    public string $year = '';

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'year'], true)) {
            $this->resetPage();
        }
    }

    public function clearFilters(): void
    {
        $this->reset('search', 'year');
    }

    public function toggleLike(int $articleId): void
    {
        $article = $this->noticiasQuery()->findOrFail($articleId);
        $user = auth()->user();

        if ($article->isLikedBy($user)) {
            $article->likedByUsers()->detach($user->id);
        } else {
            $article->likedByUsers()->attach($user->id);
        }
    }

    private function noticiasQuery()
    {
        return Article::published()->whereHas('categories', fn ($q) => $q->where('slug', 'noticia'));
    }

    /**
     * Isolada num island (ver noticias-index.blade.php): precisa estar
     * acessível via $this-> porque islands não têm acesso a variáveis de
     * view vindas de fora deles, só a propriedades/métodos do componente.
     */
    #[Computed]
    public function articles()
    {
        $query = $this->noticiasQuery()->with(['categories', 'author']);

        if ($this->search !== '') {
            $query->where(function ($q) {
                $q->where('title', 'like', '%'.$this->search.'%')
                    ->orWhere('excerpt', 'like', '%'.$this->search.'%');
            });
        }

        if ($this->year !== '') {
            $query->whereYear('published_at', $this->year);
        }

        return $query->orderByDesc('published_at')->paginate(6);
    }

    public function render()
    {
        // Agrupa os anos em PHP (em vez de YEAR()/strftime() no banco) pra
        // não depender de uma função específica do driver SQLite/MySQL.
        $archiveYears = $this->noticiasQuery()
            ->whereNotNull('published_at')
            ->get(['published_at'])
            ->groupBy(fn ($article) => $article->published_at->year)
            ->map->count()
            ->sortKeysDesc();

        return view('livewire.site.noticias.noticias-index', [
            'archiveYears' => $archiveYears,
            'popularPosts' => $this->noticiasQuery()->withCount('likedByUsers')->orderByDesc('liked_by_users_count')->take(3)->get(),
            'popularTags' => Article::popularTags(),
        ]);
    }
}
