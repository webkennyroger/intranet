<?php

namespace App\Livewire\Admin\Trainings;

use App\Models\Training;
use App\Models\TrainingCategory;
use App\Services\ActivityLogger;
use Livewire\Component;

/**
 * Formulário admin de criação/edição de capacitações (título, descrição,
 * um ou mais links de acesso/inscrição, carga horária e data de início).
 */
class TrainingForm extends Component
{
    public ?Training $training = null;

    public string $title = '';

    public string $description = '';

    /** @var array<int, array{label: string, url: string}> */
    public array $links = [['label' => '', 'url' => '']];

    public string $workloadHours = '';

    public string $startsAt = '';

    public string $trainingCategoryId = '';

    public function mount(?Training $training = null): void
    {
        if ($training) {
            $this->training = $training;
            $this->title = $training->title;
            $this->description = (string) $training->description;
            $this->links = collect($training->links)
                ->map(fn (array $link) => ['label' => $link['label'] ?? '', 'url' => $link['url'] ?? ''])
                ->all();
            $this->workloadHours = (string) ($training->workload_hours ?? '');
            $this->startsAt = $training->starts_at?->format('Y-m-d') ?? '';
            $this->trainingCategoryId = (string) $training->training_category_id;
        }

        if ($this->links === []) {
            $this->links = [['label' => '', 'url' => '']];
        }
    }

    public function addLink(): void
    {
        $this->links[] = ['label' => '', 'url' => ''];
    }

    public function removeLink(int $index): void
    {
        unset($this->links[$index]);
        $this->links = array_values($this->links);

        if ($this->links === []) {
            $this->links = [['label' => '', 'url' => '']];
        }
    }

    public function save(): void
    {
        abort_unless(auth()->user()->can($this->training ? 'trainings.update' : 'trainings.create'), 403);

        $this->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'links.*.url' => 'nullable|url|max:2048',
            'links.*.label' => 'nullable|string|max:255',
            'workloadHours' => 'nullable|integer|min:1|max:1000',
            'startsAt' => 'nullable|date',
            'trainingCategoryId' => 'nullable|exists:categories,id,type,training',
        ]);

        $links = collect($this->links)
            ->filter(fn (array $link) => trim($link['url']) !== '')
            ->map(fn (array $link) => [
                'label' => trim($link['label']) !== '' ? trim($link['label']) : null,
                'url' => trim($link['url']),
            ])
            ->values()
            ->all();

        if ($links === []) {
            $this->addError('links', 'Informe pelo menos um link.');

            return;
        }

        $data = [
            'title' => $this->title,
            'description' => $this->description !== '' ? $this->description : null,
            'links' => $links,
            'workload_hours' => $this->workloadHours !== '' ? (int) $this->workloadHours : null,
            'starts_at' => $this->startsAt !== '' ? $this->startsAt : null,
            'training_category_id' => $this->trainingCategoryId !== '' ? $this->trainingCategoryId : null,
        ];

        if ($this->training) {
            $changes = ActivityLogger::diff($this->training, $data);
            $this->training->update($data);
            if ($changes !== []) {
                ActivityLogger::updated($this->training, $changes);
            }
            $this->dispatch('toast', title: 'Capacitação', message: 'Capacitação atualizada com sucesso.', variant: 'success');
        } else {
            // Capacitação nova entra publicada — despublicar é uma ação
            // separada na tabela (togglePublish), não um campo deste
            // formulário.
            $training = Training::create($data + ['is_published' => true]);
            ActivityLogger::created($training);
            $this->dispatch('toast', title: 'Capacitação', message: 'Capacitação criada com sucesso.', variant: 'success');
        }

        $this->dispatch('training-saved');
        $this->reset(['title', 'description', 'links', 'workloadHours', 'startsAt', 'trainingCategoryId']);
    }

    public function render()
    {
        return view('livewire.admin.trainings.training-form', [
            'categories' => TrainingCategory::orderBy('name')->get(),
        ]);
    }
}
