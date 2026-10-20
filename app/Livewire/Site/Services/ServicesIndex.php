<?php

namespace App\Livewire\Site\Services;

use App\Models\ServiceLink;
use Livewire\Component;

/**
 * Página de serviços: lista os links de serviço ativos, na ordem de
 * exibição configurada, como um portal de atalhos para o usuário.
 */
class ServicesIndex extends Component
{
    public function render()
    {
        return view('livewire.site.services.services-index', [
            'links' => ServiceLink::where('is_active', true)->orderBy('order')->get(),
        ]);
    }
}
