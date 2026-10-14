<?php

namespace App\Livewire\Admin\Documents;

use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\Media;
use App\Services\ActivityLogger;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

/**
 * Formulário admin de criação/edição de documentos: título, descrição
 * (texto livre), link (abre uma página — independente da descrição),
 * pasta/categoria e arquivo — que pode ser um upload novo (um único campo
 * de arrastar-e-soltar que aceita um ou vários arquivos de uma vez, igual
 * ao de Fotos) ou um arquivo já existente na Biblioteca de Mídia (evita
 * duplicar upload). Soltar vários arquivos de uma vez, só na criação, cria
 * um Documento por arquivo (título = nome do arquivo), ignorando os campos
 * de título/descrição/link do formulário. Documento novo entra sempre
 * publicado — despublicar é uma ação separada na tabela, não um campo
 * deste formulário.
 */
class DocumentForm extends Component
{
    use WithFileUploads;

    public ?Document $document = null;

    #[Validate('required|string|max:255')]
    public string $title = '';

    #[Validate('nullable|string|max:1000')]
    public string $description = '';

    #[Validate('nullable|url|max:2048')]
    public string $link = '';

    public string $documentCategoryId = '';

    /** @var array<int, TemporaryUploadedFile> */
    public array $files = [];

    public bool $useExistingFile = false;

    public string $existingFileSearch = '';

    public ?string $existingFilePath = null;

    public ?string $existingFileLabel = null;

    public function mount(?Document $document = null): void
    {
        if ($document) {
            $this->document = $document;
            $this->title = $document->title;
            $this->description = (string) $document->description;
            $this->link = (string) $document->link;
            $this->documentCategoryId = (string) $document->document_category_id;
        }
    }

    public function pickExistingFile(string $path, string $label): void
    {
        $this->existingFilePath = $path;
        $this->existingFileLabel = $label;

        if ($this->title === '') {
            $this->title = $label;
        }
    }

    public function clearExistingFile(): void
    {
        $this->existingFilePath = null;
        $this->existingFileLabel = null;
    }

    public function removeFile(int $index): void
    {
        unset($this->files[$index]);
        $this->files = array_values($this->files);
    }

    public function save(): void
    {
        if (! $this->document && count($this->files) > 1) {
            $this->saveBulk();

            return;
        }

        abort_unless(auth()->user()->can($this->document ? 'documents.update' : 'documents.create'), 403);

        $this->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'link' => 'nullable|url|max:2048',
            'files.*' => 'file|max:10240',
            'documentCategoryId' => 'nullable',
        ]);

        $file = $this->files[0] ?? null;
        $hasAnyFile = ($this->useExistingFile ? $this->existingFilePath : $file) || $this->link !== '';

        if (! $this->document && ! $hasAnyFile) {
            $this->addError('files', 'Envie um arquivo, escolha um já existente na Biblioteca de Mídia, ou informe um link.');

            return;
        }

        $data = [
            'title' => $this->title,
            'description' => $this->description !== '' ? $this->description : null,
            'link' => $this->link !== '' ? $this->link : null,
            'document_category_id' => $this->documentCategoryId !== '' ? $this->documentCategoryId : null,
        ];

        if ($this->useExistingFile && $this->existingFilePath) {
            $data['file_path'] = $this->existingFilePath;
        } elseif (! $this->useExistingFile && $file) {
            $data['file_path'] = $file->store('documents', 'public');
        }

        if ($this->document) {
            $changes = ActivityLogger::diff($this->document, $data);
            $this->document->update($data);
            if ($changes !== []) {
                ActivityLogger::updated($this->document, $changes);
            }
            $this->dispatch('toast', title: 'Documento', message: 'Documento atualizado com sucesso.', variant: 'success');
        } else {
            $document = Document::create([...$data, 'is_published' => true]);
            ActivityLogger::created($document);
            $this->dispatch('toast', title: 'Documento', message: 'Documento criado com sucesso.', variant: 'success');
        }

        $this->dispatch('document-saved');
        $this->reset(['title', 'description', 'link', 'files', 'documentCategoryId', 'useExistingFile', 'existingFilePath', 'existingFileLabel', 'existingFileSearch']);
    }

    /**
     * Mais de um arquivo soltos de uma vez no mesmo campo: um Documento por
     * arquivo, título = nome do arquivo. Título/descrição/link do
     * formulário não se aplicam aqui (cada arquivo é um documento distinto).
     */
    private function saveBulk(): void
    {
        abort_unless(auth()->user()->can('documents.create'), 403);

        $this->validate([
            'files.*' => 'file|max:10240',
            'documentCategoryId' => 'nullable',
        ]);

        $categoryId = $this->documentCategoryId !== '' ? $this->documentCategoryId : null;
        $count = 0;

        foreach ($this->files as $uploadedFile) {
            $path = $uploadedFile->store('documents', 'public');

            $document = Document::create([
                'title' => pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME),
                'description' => null,
                'document_category_id' => $categoryId,
                'file_path' => $path,
                'is_published' => true,
            ]);
            ActivityLogger::created($document);
            $count++;
        }

        $this->dispatch('toast', title: 'Documentos', message: "{$count} documento(s) criado(s) com sucesso.", variant: 'success');
        $this->dispatch('document-saved');
        $this->reset(['files', 'documentCategoryId']);
    }

    /**
     * Arquivos já existentes (documentos cadastrados + uploads diretos da
     * Biblioteca de Mídia que não sejam imagem), pra reaproveitar sem
     * precisar enviar de novo. Não entra arquivo de capa de artigo, foto de
     * álbum ou foto do Conselho — só o que já é, de fato, um "documento".
     *
     * @return Collection<int, array{path: string, name: string, source: string}>
     */
    private function existingFileOptions(): Collection
    {
        $options = collect();

        Document::whereNotNull('file_path')
            ->when($this->document, fn ($q) => $q->where('id', '!=', $this->document->id))
            ->get()
            ->each(function (Document $d) use ($options) {
                $options->push(['path' => $d->file_path, 'name' => $d->title, 'source' => 'Documentos']);
            });

        Media::all()->each(function (Media $m) use ($options) {
            $extension = strtolower(pathinfo($m->path, PATHINFO_EXTENSION));
            if (! in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'], true)) {
                $options->push(['path' => $m->path, 'name' => $m->name, 'source' => $m->source ?? 'Outros']);
            }
        });

        $options = $options->filter(fn (array $o) => Storage::disk('public')->exists($o['path']));

        if ($this->existingFileSearch !== '') {
            $options = $options->filter(fn (array $o) => str_contains(strtolower($o['name']), strtolower($this->existingFileSearch)));
        }

        return $options->values();
    }

    public function render()
    {
        return view('livewire.admin.documents.document-form', [
            'categories' => DocumentCategory::orderBy('name')->get(),
            'existingFileOptions' => $this->existingFileOptions(),
        ]);
    }
}
