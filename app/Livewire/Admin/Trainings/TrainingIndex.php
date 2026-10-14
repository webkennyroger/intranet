<?php

namespace App\Livewire\Admin\Trainings;

use App\Models\Training;
use App\Services\ActivityLogger;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Tela admin "Capacitações": lista os cursos/treinamentos cadastrados,
 * permitindo editar, publicar/despublicar e excluir cada um.
 */
class TrainingIndex extends Component
{
    use WithPagination;

    public ?int $editingTrainingId = null;

    /** @var array<int, int> */
    public array $selected = [];

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
        $pageIds = Training::latest()->paginate(10)->pluck('id')->all();

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

    public function render()
    {
        $trainings = Training::latest()->paginate(10);

        return view('livewire.admin.trainings.training-index', [
            'trainings' => $trainings,
            'allSelectedOnPage' => $trainings->isNotEmpty() && array_diff($trainings->pluck('id')->all(), $this->selected) === [],
            'editingTraining' => $this->editingTrainingId ? Training::find($this->editingTrainingId) : null,
            'totalTrainings' => Training::count(),
            'publishedTrainings' => Training::where('is_published', true)->count(),
        ])->layout('layouts.admin', ['title' => 'Capacitações']);
    }
}
