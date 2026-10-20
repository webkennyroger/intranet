<?php

namespace App\Livewire\Admin\Documents;

use App\Livewire\Concerns\ComputesWeeklyTrend;
use App\Models\Document;
use App\Models\DocumentCategory;
use App\Services\ActivityLogger;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Tela admin "Documentos": aba "Documentos" lista os documentos cadastrados
 * (editar, publicar/despublicar, excluir); aba "Pastas" gerencia as
 * categorias usadas para organizá-los (criar, editar, excluir — excluir uma
 * pasta não exclui os documentos dela, eles só ficam sem pasta).
 */
class DocumentIndex extends Component
{
    use ComputesWeeklyTrend, WithPagination;

    public string $activeTab = 'documentos';

    public string $search = '';

    public ?int $editingDocumentId = null;

    /** @var array<int, int> */
    public array $selected = [];

    public function updatedSearch(): void
    {
        $this->resetPage();
        $this->selected = [];
    }

    public ?DocumentCategory $editingCategory = null;

    public string $categoryName = '';

    public string $categoryIcon = 'folder';

    public string $categoryColor = '#2563EB';

    #[On('document-saved')]
    public function refreshList(): void
    {
        $this->editingDocumentId = null;
        $this->dispatch('close-modal', 'document-form');
    }

    public function edit(int $documentId): void
    {
        $this->editingDocumentId = $documentId;
    }

    public function closeForm(): void
    {
        $this->editingDocumentId = null;
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function editCategory(int $categoryId): void
    {
        $category = DocumentCategory::findOrFail($categoryId);

        $this->editingCategory = $category;
        $this->categoryName = $category->name;
        $this->categoryIcon = $category->icon;
        $this->categoryColor = $category->color;
    }

    public function cancelCategoryEdit(): void
    {
        $this->reset(['editingCategory', 'categoryName', 'categoryIcon', 'categoryColor']);
    }

    public function saveCategory(): void
    {
        abort_unless(auth()->user()->can($this->editingCategory ? 'documents.update' : 'documents.create'), 403);

        $this->validate([
            'categoryName' => 'required|string|max:255',
            'categoryIcon' => 'required|string|max:50',
            'categoryColor' => 'required|regex:/^#[0-9a-fA-F]{6}$/',
        ]);

        $slug = Str::slug($this->categoryName);
        $slugTaken = DocumentCategory::where('slug', $slug)
            ->when($this->editingCategory, fn ($query) => $query->where('id', '!=', $this->editingCategory->id))
            ->exists();

        if ($slugTaken) {
            $this->addError('categoryName', 'Já existe uma pasta com esse nome.');

            return;
        }

        $data = [
            'name' => $this->categoryName,
            'slug' => $slug,
            'icon' => $this->categoryIcon,
            'color' => $this->categoryColor,
        ];

        if ($this->editingCategory) {
            $changes = ActivityLogger::diff($this->editingCategory, $data);
            $this->editingCategory->update($data);
            if ($changes !== []) {
                ActivityLogger::updated($this->editingCategory, $changes);
            }
            $this->dispatch('toast', title: 'Pasta', message: 'Pasta atualizada com sucesso.', variant: 'success');
        } else {
            $category = DocumentCategory::create($data);
            ActivityLogger::created($category);
            $this->dispatch('toast', title: 'Pasta', message: 'Pasta criada com sucesso.', variant: 'success');
        }

        $this->cancelCategoryEdit();
    }

    public function deleteCategory(int $categoryId): void
    {
        abort_unless(auth()->user()->can('documents.delete'), 403);

        $category = DocumentCategory::findOrFail($categoryId);
        $category->delete();
        ActivityLogger::deleted($category);
        $this->dispatch('toast', title: 'Pasta', message: 'Pasta excluída com sucesso.', variant: 'danger');

        if ($this->editingCategory?->id === $categoryId) {
            $this->cancelCategoryEdit();
        }
    }

    public function togglePublish(int $documentId): void
    {
        abort_unless(auth()->user()->can('documents.update'), 403);

        $document = Document::findOrFail($documentId);
        $willBePublished = ! $document->is_published;
        $document->update(['is_published' => $willBePublished]);
        $willBePublished ? ActivityLogger::activated($document) : ActivityLogger::deactivated($document);
        $this->dispatch('toast', title: 'Documento', message: $willBePublished ? 'Documento publicado com sucesso.' : 'Documento despublicado.', variant: $willBePublished ? 'success' : 'warning');
    }

    public function delete(int $documentId): void
    {
        abort_unless(auth()->user()->can('documents.delete'), 403);

        $document = Document::findOrFail($documentId);
        $document->delete();
        ActivityLogger::deleted($document);
        $this->dispatch('toast', title: 'Documento', message: 'Documento excluído com sucesso.', variant: 'danger');
    }

    public function toggleSelectAllOnPage(): void
    {
        $pageIds = $this->documents->pluck('id')->all();

        if (array_diff($pageIds, $this->selected) === []) {
            $this->selected = array_values(array_diff($this->selected, $pageIds));
        } else {
            $this->selected = array_values(array_unique(array_merge($this->selected, $pageIds)));
        }
    }

    public function deselectAll(): void
    {
        $this->selected = [];
    }

    public function bulkPublish(): void
    {
        abort_unless(auth()->user()->can('documents.update'), 403);

        $documents = Document::whereIn('id', $this->selected)->where('is_published', false)->get();

        foreach ($documents as $document) {
            $document->update(['is_published' => true]);
            ActivityLogger::activated($document);
        }

        $this->selected = [];
        $message = $documents->isNotEmpty() ? $documents->count().' documento(s) publicado(s) com sucesso.' : 'Nenhum rascunho selecionado para publicar.';
        $this->dispatch('toast', title: 'Documentos', message: $message, variant: $documents->isNotEmpty() ? 'success' : 'warning');
    }

    public function bulkUnpublish(): void
    {
        abort_unless(auth()->user()->can('documents.update'), 403);

        $documents = Document::whereIn('id', $this->selected)->where('is_published', true)->get();

        foreach ($documents as $document) {
            $document->update(['is_published' => false]);
            ActivityLogger::deactivated($document);
        }

        $this->selected = [];
        $message = $documents->isNotEmpty() ? $documents->count().' documento(s) despublicado(s).' : 'Nenhum documento publicado selecionado.';
        $this->dispatch('toast', title: 'Documentos', message: $message, variant: 'warning');
    }

    public function bulkDelete(): void
    {
        abort_unless(auth()->user()->can('documents.delete'), 403);

        $documents = Document::whereIn('id', $this->selected)->get();

        foreach ($documents as $document) {
            $document->delete();
            ActivityLogger::deleted($document);
        }

        $this->selected = [];
        $this->dispatch('toast', title: 'Documentos', message: $documents->count().' documento(s) excluído(s) com sucesso.', variant: 'danger');
    }

    /**
     * Isolada num island (ver document-index.blade.php): precisa estar
     * acessível via $this-> porque islands não têm acesso a variáveis de
     * view vindas de fora deles, só a propriedades/métodos do componente.
     */
    #[Computed]
    public function documents()
    {
        return Document::with('category')
            ->when($this->search !== '', fn ($query) => $query->where('title', 'like', "%{$this->search}%"))
            ->latest()->paginate(10);
    }

    #[Computed]
    public function allSelectedOnPage(): bool
    {
        return $this->documents->isNotEmpty() && array_diff($this->documents->pluck('id')->all(), $this->selected) === [];
    }

    public function render()
    {
        return view('livewire.admin.documents.document-index', [
            'editingDocument' => $this->editingDocumentId ? Document::find($this->editingDocumentId) : null,
            'totalDocuments' => Document::count(),
            'totalDocumentsTrend' => $this->weeklyTrend(Document::query(), 'created_at'),
            'publishedDocuments' => Document::where('is_published', true)->count(),
            'publishedDocumentsTrend' => $this->weeklyTrend(Document::query()->where('is_published', true), 'created_at'),
            'draftDocuments' => Document::where('is_published', false)->count(),
            'draftDocumentsTrend' => $this->weeklyTrend(Document::query()->where('is_published', false), 'created_at'),
            'categories' => DocumentCategory::withCount('documents')->orderBy('name')->get(),
        ])->layout('layouts.admin', ['title' => 'Documentos']);
    }
}
