<?php

namespace App\Livewire\Noticias;

use App\Models\Article;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Página de Notícias: lista as matérias publicadas que pertencem à
 * categoria "noticia", mais recentes primeiro.
 */
class NoticiasIndex extends Component
{
    use WithPagination;

    public function render()
    {
        return view('livewire.noticias.noticias-index', [
            'articles' => Article::published()
                ->whereHas('category', fn ($query) => $query->where('slug', 'noticia'))
                ->orderByDesc('published_at')
                ->paginate(10),
        ]);
    }
}
