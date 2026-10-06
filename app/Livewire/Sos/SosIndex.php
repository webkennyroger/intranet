<?php

namespace App\Livewire\Sos;

use App\Models\Article;
use Livewire\Component;

/**
 * Página SOS DPE-MT: lista as matérias publicadas da categoria
 * "sos-dpemt", mais recentes primeiro.
 */
class SosIndex extends Component
{
    public function render()
    {
        return view('livewire.sos.sos-index', [
            'articles' => Article::published()
                ->whereHas('category', fn ($query) => $query->where('slug', 'sos-dpemt'))
                ->orderByDesc('published_at')
                ->get(),
        ]);
    }
}
