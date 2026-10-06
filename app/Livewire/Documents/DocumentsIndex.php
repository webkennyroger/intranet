<?php

namespace App\Livewire\Documents;

use App\Models\Document;
use Livewire\Component;

/**
 * Página de documentos: lista os documentos publicados, mais
 * recentes primeiro, disponíveis para consulta pelos usuários.
 */
class DocumentsIndex extends Component
{
    public function render()
    {
        return view('livewire.documents.documents-index', [
            'documents' => Document::where('is_published', true)->latest()->get(),
        ]);
    }
}
