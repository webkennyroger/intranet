<?php

namespace App\Livewire\Admin\Documents;

use App\Models\Document;
use App\Services\ActivityLogger;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Tela admin "Documentos": lista os documentos cadastrados, permitindo
 * editar, publicar/despublicar e excluir cada um.
 */
class DocumentIndex extends Component
{
    use WithPagination;

    public ?int $editingDocumentId = null;

    #[On('document-saved')]
    public function refreshList(): void
    {
        $this->editingDocumentId = null;
    }

    public function edit(int $documentId): void
    {
        $this->editingDocumentId = $documentId;
    }

    public function closeForm(): void
    {
        $this->editingDocumentId = null;
    }

    public function togglePublish(int $documentId): void
    {
        abort_unless(auth()->user()->can('content.publish'), 403);

        $document = Document::findOrFail($documentId);
        $willBePublished = ! $document->is_published;
        $document->update(['is_published' => $willBePublished]);
        $willBePublished ? ActivityLogger::activated($document) : ActivityLogger::deactivated($document);
    }

    public function delete(int $documentId): void
    {
        abort_unless(auth()->user()->can('content.delete'), 403);

        $document = Document::findOrFail($documentId);
        $document->delete();
        ActivityLogger::deleted($document);
    }

    public function render()
    {
        return view('livewire.admin.documents.document-index', [
            'documents' => Document::latest()->paginate(10),
            'editingDocument' => $this->editingDocumentId ? Document::find($this->editingDocumentId) : null,
            'totalDocuments' => Document::count(),
            'publishedDocuments' => Document::where('is_published', true)->count(),
            'draftDocuments' => Document::where('is_published', false)->count(),
        ])->layout('layouts.admin', ['title' => 'Documentos']);
    }
}
