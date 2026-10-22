<?php

namespace App\Livewire\Admin\Trainings;

use App\Models\Training;
use App\Models\TrainingCategory;
use App\Services\ActivityLogger;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Tela admin "Capacitações": aba "Capacitações" lista os cursos/treinamentos
 * cadastrados (editar, publicar/despublicar, excluir); aba "Pastas" gerencia
 * as categorias usadas para organizá-los (criar, editar, excluir — excluir
 * uma pasta não exclui as capacitações dela, elas só ficam sem pasta).
 */
class TrainingIndex extends Component
{
    use WithPagination;

    public string $activeTab = 'capacitacoes';

    public string $search = '';

    public ?int $editingTrainingId = null;

    /** @var array<int, int> */
    public array $selected = [];

    public function updatedSearch(): void
    {
        $this->resetPage();
        $this->selected = [];
    }

    public ?TrainingCategory $editingCategory = null;

    public string $categoryName = '';

    public string $categoryIcon = 'folder';

    public string $categoryColor = '#2563EB';

    #[On('training-saved')]
    public function refreshList(): void
    {
        $this->editingTrainingId = null;
        $this->dispatch('close-modal', 'training-form');
    }

    public function edit(int $trainingId): void
    {
        $this->editingTrainingId = $trainingId;
    }

    public function closeForm(): void
    {
        $this->editingTrainingId = null;
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function editCategory(int $categoryId): void
    {
        $category = TrainingCategory::findOrFail($categoryId);

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
        abort_unless(auth()->user()->can($this->editingCategory ? 'trainings.update' : 'trainings.create'), 403);

        $this->validate([
            'categoryName' => 'required|string|max:255',
            'categoryIcon' => 'required|string|max:50',
            'categoryColor' => 'required|regex:/^#[0-9a-fA-F]{6}$/',
        ]);

        $slug = Str::slug($this->categoryName);
        $slugTaken = TrainingCategory::where('slug', $slug)
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
            $category = TrainingCategory::create($data);
            ActivityLogger::created($category);
            $this->dispatch('toast', title: 'Pasta', message: 'Pasta criada com sucesso.', variant: 'success');
        }

        $this->cancelCategoryEdit();
    }

    public function deleteCategory(int $categoryId): void
    {
        abort_unless(auth()->user()->can('trainings.delete'), 403);

        $category = TrainingCategory::findOrFail($categoryId);
        $category->delete();
        ActivityLogger::deleted($category);
        $this->dispatch('toast', title: 'Pasta', message: 'Pasta excluída com sucesso.', variant: 'danger');

        if ($this->editingCategory?->id === $categoryId) {
            $this->cancelCategoryEdit();
        }
    }

    public function togglePublish(int $trainingId): void
    {
        abort_unless(auth()->user()->can('trainings.update'), 403);

        $training = Training::findOrFail($trainingId);
        $willBePublished = ! $training->is_published;
        $training->update(['is_published' => $willBePublished]);
        $willBePublished ? ActivityLogger::activated($training) : ActivityLogger::deactivated($training);
        $this->dispatch('toast', title: 'Capacitação', message: $willBePublished ? 'Capacitação publicada com sucesso.' : 'Capacitação despublicada.', variant: $willBePublished ? 'success' : 'warning');
    }

    public function delete(int $trainingId): void
    {
        abort_unless(auth()->user()->can('trainings.delete'), 403);

        $training = Training::findOrFail($trainingId);
        $training->delete();
        ActivityLogger::deleted($training);
        $this->dispatch('toast', title: 'Capacitação', message: 'Capacitação excluída com sucesso.', variant: 'danger');
    }

    public function toggleSelectAllOnPage(): void
    {
        $pageIds = $this->trainings->pluck('id')->all();

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
        abort_unless(auth()->user()->can('trainings.update'), 403);

        $trainings = Training::whereIn('id', $this->selected)->where('is_published', false)->get();

        foreach ($trainings as $training) {
            $training->update(['is_published' => true]);
            ActivityLogger::activated($training);
        }

        $this->selected = [];
        $message = $trainings->isNotEmpty() ? $trainings->count().' capacitação(ões) publicada(s) com sucesso.' : 'Nenhum rascunho selecionado para publicar.';
        $this->dispatch('toast', title: 'Capacitações', message: $message, variant: $trainings->isNotEmpty() ? 'success' : 'warning');
    }

    public function bulkUnpublish(): void
    {
        abort_unless(auth()->user()->can('trainings.update'), 403);

        $trainings = Training::whereIn('id', $this->selected)->where('is_published', true)->get();

        foreach ($trainings as $training) {
            $training->update(['is_published' => false]);
            ActivityLogger::deactivated($training);
        }

        $this->selected = [];
        $message = $trainings->isNotEmpty() ? $trainings->count().' capacitação(ões) despublicada(s).' : 'Nenhuma capacitação publicada selecionada.';
        $this->dispatch('toast', title: 'Capacitações', message: $message, variant: 'warning');
    }

    public function bulkDelete(): void
    {
        abort_unless(auth()->user()->can('trainings.delete'), 403);

        $trainings = Training::whereIn('id', $this->selected)->get();

        foreach ($trainings as $training) {
            $training->delete();
            ActivityLogger::deleted($training);
        }

        $this->selected = [];
        $this->dispatch('toast', title: 'Capacitações', message: $trainings->count().' capacitação(ões) excluída(s) com sucesso.', variant: 'danger');
    }

    /**
     * Isolada num island (ver training-index.blade.php): precisa estar
     * acessível via $this-> porque islands não têm acesso a variáveis de
     * view vindas de fora deles, só a propriedades/métodos do componente.
     */
    #[Computed]
    public function trainings()
    {
        return Training::with('category')
            ->when($this->search !== '', fn ($query) => $query->where('title', 'like', "%{$this->search}%"))
            ->latest()->paginate(10);
    }

    #[Computed]
    public function allSelectedOnPage(): bool
    {
        return $this->trainings->isNotEmpty() && array_diff($this->trainings->pluck('id')->all(), $this->selected) === [];
    }

    public function render()
    {
        return view('livewire.admin.trainings.training-index', [
            'editingTraining' => $this->editingTrainingId ? Training::find($this->editingTrainingId) : null,
            'totalTrainings' => Training::count(),
            'categories' => TrainingCategory::withCount('trainings')->orderBy('name')->get(),
        ])->layout('layouts.admin', ['title' => 'Capacitações']);
    }
}
