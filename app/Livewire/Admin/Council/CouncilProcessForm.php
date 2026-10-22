<?php

namespace App\Livewire\Admin\Council;

use App\Models\CouncilProcess;
use App\Services\ActivityLogger;
use Livewire\Attributes\Validate;
use Livewire\Component;

/**
 * Formulário admin de criação/edição de um processo SEI do Conselho
 * Superior: número do processo, assunto, relator/atribuído, interessado
 * e o link externo do SEI (opcional — o número só abre o link quando
 * esse campo está preenchido).
 */
class CouncilProcessForm extends Component
{
    public ?CouncilProcess $process = null;

    #[Validate('required|string|max:50')]
    public string $seiNumber = '';

    #[Validate('required|string|max:500')]
    public string $subject = '';

    #[Validate('required|string|max:255')]
    public string $assignedTo = '';

    #[Validate('required|string|max:255')]
    public string $interestedParty = '';

    #[Validate('nullable|url|max:2048')]
    public string $externalUrl = '';

    public function mount(?CouncilProcess $process = null): void
    {
        if ($process) {
            $this->process = $process;
            $this->seiNumber = $process->sei_number;
            $this->subject = $process->subject;
            $this->assignedTo = $process->assigned_to;
            $this->interestedParty = $process->interested_party;
            $this->externalUrl = (string) $process->external_url;
        }
    }

    public function save(): void
    {
        abort_unless(auth()->user()->can($this->process ? 'council.update' : 'council.create'), 403);

        $this->validate();

        $data = [
            'sei_number' => $this->seiNumber,
            'subject' => $this->subject,
            'assigned_to' => $this->assignedTo,
            'interested_party' => $this->interestedParty,
            'external_url' => $this->externalUrl !== '' ? $this->externalUrl : null,
        ];

        if ($this->process) {
            $changes = ActivityLogger::diff($this->process, $data);
            $this->process->update($data);
            if ($changes !== []) {
                ActivityLogger::updated($this->process, $changes);
            }
            $this->dispatch('toast', title: 'Processo', message: 'Processo atualizado com sucesso.', variant: 'success');
        } else {
            $process = CouncilProcess::create([...$data, 'is_published' => true]);
            ActivityLogger::created($process);
            $this->dispatch('toast', title: 'Processo', message: 'Processo criado com sucesso.', variant: 'success');
        }

        $this->dispatch('council-process-saved');
        $this->reset(['seiNumber', 'subject', 'assignedTo', 'interestedParty', 'externalUrl']);
    }

    public function render()
    {
        return view('livewire.admin.council.council-process-form');
    }
}
