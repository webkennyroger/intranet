<?php

namespace App\Livewire\Site\Mural;

use App\Models\Article;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Lista pública de todas as matérias (de qualquer categoria — Mural,
 * Notícias ou SOS) marcadas com uma determinada tag, acessada ao clicar
 * num selo #tag na página de leitura de uma matéria.
 */
class ArticlesByTag extends Component
{
    use WithPagination;

    public string $tag;

    public function mount(string $tag): void
    {
        $this->tag = $tag;
    }

    private function taggedQuery()
    {
        // A tag é só o nome da categoria: filtra pela relação em vez do
        // campo `tags` (uma cópia preenchida só ao salvar a matéria, que
        // fica desatualizada para matérias antigas, nunca resalvas).
        return Article::published()->whereHas('categories', fn ($query) => $query->where('name', $this->tag));
    }

    public function toggleLike(int $articleId): void
    {
        $article = $this->taggedQuery()->findOrFail($articleId);
        $user = auth()->user();

        if ($article->isLikedBy($user)) {
            $article->likedByUsers()->detach($user->id);
        } else {
            $article->likedByUsers()->attach($user->id);
        }
    }

    /**
     * Isolada num island (ver articles-by-tag.blade.php): precisa estar
     * acessível via $this-> porque islands não têm acesso a variáveis de
     * view vindas de fora deles, só a propriedades/métodos do componente.
     */
    #[Computed]
    public function articles()
    {
        return $this->taggedQuery()
            ->with('categories')
            ->orderByDesc('published_at')
            ->paginate(8);
    }

    public function render()
    {
        return view('livewire.site.mural.articles-by-tag', [
            'popularTags' => Article::popularTags(),
        ]);
    }
}
