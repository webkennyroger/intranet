<?php

namespace App\Livewire\Admin\Documents;

use App\Models\Document;
use App\Services\ActivityLogger;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Formulário admin de criação/edição de documentos (upload de arquivo,
 * título, descrição e status de publicação).
 */
class DocumentForm extends Component
{
    use WithFileUploads;

    public ?Document $document = null;

    #[Validate('required|string|max:255')]
    public string $title = '';

    #[Validate('nullable|string|max:1000')]
    public string $description = '';

    #[Validate('nullable|file|max:10240')]
    public $file = null;

    public bool $isPublished = true;

    public function mount(?Document $document = null): void
    {
        if ($document) {
            $this->document = $document;
            $this->title = $document->title;
            $this->description = (string) $document->description;
            $this->isPublished = $document->is_published;
        }
    }

    public function save(): void
    {
        abort_unless(auth()->user()->can($this->document ? 'content.update' : 'content.create'), 403);

        $this->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'file' => ($this->document ? 'nullable' : 'required').'|file|max:10240',
            'isPublished' => 'boolean',
        ]);

        $data = [
            'title' => $this->title,
            'description' => $this->description !== '' ? $this->description : null,
            'is_published' => $this->isPublished,
        ];

        if ($this->file) {
            $data['file_path'] = $this->file->store('documents', 'public');
        }

        if ($this->document) {
            $changes = ActivityLogger::diff($this->document, $data);
            $this->document->update($data);
            if ($changes !== []) {
                ActivityLogger::updated($this->document, $changes);
            }
        } else {
            $document = Document::create($data);
            ActivityLogger::created($document);
        }

        $this->dispatch('document-saved');
        $this->reset(['title', 'description', 'file']);
        $this->isPublished = true;
    }

    public function render()
    {
        return view('livewire.admin.documents.document-form');
    }
}
