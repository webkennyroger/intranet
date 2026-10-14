<?php

namespace App\Livewire\Documents;

use App\Models\Document;
use App\Models\DocumentCategory;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Página de documentos: lista os documentos publicados, com busca por
 * palavra-chave, filtro por pasta/categoria e por período, organizados
 * em "pastas" (DocumentCategory) como no sistema de referência.
 */
class DocumentsIndex extends Component
{
    use WithPagination;

    public string $search = '';

    public string $categoryId = '';

    public string $period = '';

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'categoryId', 'period'], true)) {
            $this->resetPage();
        }
    }

    public function clearFilters(): void
    {
        $this->reset('search', 'categoryId', 'period');
    }

    public function selectCategory(int $categoryId): void
    {
        $this->categoryId = (string) ($this->categoryId === (string) $categoryId ? '' : $categoryId);
        $this->resetPage();
    }

    public function render()
    {
        $query = Document::with('category')->where('is_published', true);

        if ($this->search !== '') {
            $query->where(function ($q) {
                $q->where('title', 'like', '%'.$this->search.'%')
                    ->orWhere('description', 'like', '%'.$this->search.'%');
            });
        }

        if ($this->categoryId !== '') {
            $query->where('document_category_id', $this->categoryId);
        }

        if ($this->period !== '') {
            $from = match ($this->period) {
                '7d' => now()->subDays(7),
                '30d' => now()->subDays(30),
                'year' => now()->startOfYear(),
                default => null,
            };

            if ($from) {
                $query->where('created_at', '>=', $from);
            }
        }

        $categories = DocumentCategory::orderBy('name')
            ->withCount(['documents' => fn ($q) => $q->where('is_published', true)])
            ->get();

        return view('livewire.documents.documents-index', [
            'documents' => $query->latest()->paginate(10),
            'categories' => $categories,
            'totalPublished' => Document::where('is_published', true)->count(),
        ]);
    }
}
