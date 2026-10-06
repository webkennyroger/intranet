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

    #[On('training-saved')]
    public function refreshList(): void
    {
        $this->editingTrainingId = null;
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
        abort_unless(auth()->user()->can('content.publish'), 403);

        $training = Training::findOrFail($trainingId);
        $willBePublished = ! $training->is_published;
        $training->update(['is_published' => $willBePublished]);
        $willBePublished ? ActivityLogger::activated($training) : ActivityLogger::deactivated($training);
    }

    public function delete(int $trainingId): void
    {
        abort_unless(auth()->user()->can('content.delete'), 403);

        $training = Training::findOrFail($trainingId);
        $training->delete();
        ActivityLogger::deleted($training);
    }

    public function render()
    {
        return view('livewire.admin.trainings.training-index', [
            'trainings' => Training::latest()->paginate(10),
            'editingTraining' => $this->editingTrainingId ? Training::find($this->editingTrainingId) : null,
            'totalTrainings' => Training::count(),
            'publishedTrainings' => Training::where('is_published', true)->count(),
        ])->layout('layouts.admin', ['title' => 'Capacitações']);
    }
}
