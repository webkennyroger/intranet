<?php

namespace App\Livewire\Council;

use App\Models\CouncilMember;
use Livewire\Component;

/**
 * Página institucional do conselho: lista os membros ativos do
 * conselho, na ordem de exibição definida no cadastro.
 */
class CouncilIndex extends Component
{
    public function render()
    {
        return view('livewire.council.council-index', [
            'members' => CouncilMember::where('is_active', true)->orderBy('order')->get(),
        ]);
    }
}
