<?php

namespace App\Livewire\Admin\Trainings;

use App\Models\Training;
use App\Services\ActivityLogger;
use Livewire\Attributes\Validate;
use Livewire\Component;

/**
 * Formulário admin de criação/edição de capacitações (título, descrição,
 * link externo do curso, carga horária, data de início e publicação).
 */
class TrainingForm extends Component
{
    public ?Training $training = null;

    #[Validate('required|string|max:255')]
    public string $title = '';

    #[Validate('nullable|string|max:1000')]
    public string $description = '';

    #[Validate('required|url|max:2048')]
    public string $externalUrl = '';

    #[Validate('nullable|integer|min:1|max:1000')]
    public string $workloadHours = '';

    #[Validate('nullable|date')]
    public string $startsAt = '';

    public bool $isPublished = true;

    public function mount(?Training $training = null): void
    {
        if ($training) {
            $this->training = $training;
            $this->title = $training->title;
            $this->description = (string) $training->description;
            $this->externalUrl = $training->external_url;
            $this->workloadHours = (string) ($training->workload_hours ?? '');
            $this->startsAt = $training->starts_at?->format('Y-m-d') ?? '';
            $this->isPublished = $training->is_published;
        }
    }

    public function save(): void
    {
        abort_unless(auth()->user()->can($this->training ? 'content.update' : 'content.create'), 403);

        $this->validate();

        $data = [
            'title' => $this->title,
            'description' => $this->description !== '' ? $this->description : null,
            'external_url' => $this->externalUrl,
            'workload_hours' => $this->workloadHours !== '' ? (int) $this->workloadHours : null,
            'starts_at' => $this->startsAt !== '' ? $this->startsAt : null,
            'is_published' => $this->isPublished,
        ];

        if ($this->training) {
            $changes = ActivityLogger::diff($this->training, $data);
            $this->training->update($data);
            if ($changes !== []) {
                ActivityLogger::updated($this->training, $changes);
            }
        } else {
            $training = Training::create($data);
            ActivityLogger::created($training);
        }

        $this->dispatch('training-saved');
        $this->reset(['title', 'description', 'externalUrl', 'workloadHours', 'startsAt']);
        $this->isPublished = true;
    }

    public function render()
    {
        return view('livewire.admin.trainings.training-form');
    }
}
