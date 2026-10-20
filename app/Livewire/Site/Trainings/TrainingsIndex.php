<?php

namespace App\Livewire\Site\Trainings;

use App\Models\Training;
use App\Models\TrainingCategory;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Página de capacitações: lista os cursos/treinamentos publicados, com
 * busca por palavra-chave, filtro por pasta/categoria e por período,
 * mesmo padrão de busca e paginação da página de Documentos.
 */
class TrainingsIndex extends Component
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

    /**
     * Isolada num island (ver trainings-index.blade.php): precisa estar
     * acessível via $this-> porque islands não têm acesso a variáveis de
     * view vindas de fora deles, só a propriedades/métodos do componente.
     */
    #[Computed]
    public function trainings()
    {
        $query = Training::with('category')->where('is_published', true);

        if ($this->search !== '') {
            $query->where(function ($q) {
                $q->where('title', 'like', '%'.$this->search.'%')
                    ->orWhere('description', 'like', '%'.$this->search.'%');
            });
        }

        if ($this->categoryId !== '') {
            $query->where('training_category_id', $this->categoryId);
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

        return $query->latest()->paginate(10);
    }

    public function render()
    {
        $categories = TrainingCategory::orderBy('name')
            ->withCount(['trainings' => fn ($q) => $q->where('is_published', true)])
            ->get();

        return view('livewire.site.trainings.trainings-index', [
            'categories' => $categories,
        ]);
    }
}
