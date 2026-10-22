<?php

namespace App\Livewire\Admin\Council;

use App\Models\CouncilProcess;
use App\Services\ActivityLogger;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Tela admin "Conselho Superior": gerencia os processos SEI do conselho
 * (busca, publicar/despublicar, exclusão). Os membros exibidos na página
 * pública não têm cadastro próprio — são os usuários com o papel
 * "Defensor", atribuído em Usuários & Acessos.
 */
class CouncilMemberIndex extends Component
{
    use WithPagination;

    // --- Processos ---

    public string $processSearch = '';

    public ?int $editingProcessId = null;

    /** @var array<int, int> */
    public array $selectedProcesses = [];

    public function updatedProcessSearch(): void
    {
        $this->resetPage();
        $this->selectedProcesses = [];
    }

    #[On('council-process-saved')]
    public function refreshProcesses(): void
    {
        $this->editingProcessId = null;
        $this->dispatch('close-modal', 'council-process-form');
    }

    public function editProcess(int $processId): void
    {
        $this->editingProcessId = $processId;
    }

    public function closeProcessForm(): void
    {
        $this->editingProcessId = null;
    }

    private function filteredProcesses()
    {
        return CouncilProcess::when(
            $this->processSearch !== '',
            fn ($query) => $query->where('sei_number', 'like', "%{$this->processSearch}%")
                ->orWhere('subject', 'like', "%{$this->processSearch}%")
                ->orWhere('assigned_to', 'like', "%{$this->processSearch}%")
                ->orWhere('interested_party', 'like', "%{$this->processSearch}%")
        );
    }

    public function togglePublish(int $processId): void
    {
        abort_unless(auth()->user()->can('council.update'), 403);

        $process = CouncilProcess::findOrFail($processId);
        $willBePublished = ! $process->is_published;
        $process->update(['is_published' => $willBePublished]);
        $willBePublished ? ActivityLogger::activated($process) : ActivityLogger::deactivated($process);
        $this->dispatch('toast', title: 'Processo', message: $willBePublished ? 'Processo publicado com sucesso.' : 'Processo despublicado.', variant: $willBePublished ? 'success' : 'warning');
    }

    public function deleteProcess(int $processId): void
    {
        abort_unless(auth()->user()->can('council.delete'), 403);

        $process = CouncilProcess::findOrFail($processId);
        $process->delete();
        ActivityLogger::deleted($process);
        $this->dispatch('toast', title: 'Processo', message: 'Processo excluído com sucesso.', variant: 'danger');
    }

    public function toggleSelectAllProcessesOnPage(): void
    {
        $pageIds = $this->processes->pluck('id')->all();

        if (array_diff($pageIds, $this->selectedProcesses) === []) {
            $this->selectedProcesses = array_values(array_diff($this->selectedProcesses, $pageIds));
        } else {
            $this->selectedProcesses = array_values(array_unique(array_merge($this->selectedProcesses, $pageIds)));
        }
    }

    public function deselectAllProcesses(): void
    {
        $this->selectedProcesses = [];
    }

    public function bulkPublish(): void
    {
        abort_unless(auth()->user()->can('council.update'), 403);

        $processes = CouncilProcess::whereIn('id', $this->selectedProcesses)->where('is_published', false)->get();

        foreach ($processes as $process) {
            $process->update(['is_published' => true]);
            ActivityLogger::activated($process);
        }

        $this->selectedProcesses = [];
        $message = $processes->isNotEmpty() ? $processes->count().' processo(s) publicado(s) com sucesso.' : 'Nenhum rascunho selecionado para publicar.';
        $this->dispatch('toast', title: 'Processos', message: $message, variant: $processes->isNotEmpty() ? 'success' : 'warning');
    }

    public function bulkUnpublish(): void
    {
        abort_unless(auth()->user()->can('council.update'), 403);

        $processes = CouncilProcess::whereIn('id', $this->selectedProcesses)->where('is_published', true)->get();

        foreach ($processes as $process) {
            $process->update(['is_published' => false]);
            ActivityLogger::deactivated($process);
        }

        $this->selectedProcesses = [];
        $message = $processes->isNotEmpty() ? $processes->count().' processo(s) despublicado(s).' : 'Nenhum processo publicado selecionado.';
        $this->dispatch('toast', title: 'Processos', message: $message, variant: 'warning');
    }

    public function bulkDeleteProcesses(): void
    {
        abort_unless(auth()->user()->can('council.delete'), 403);

        $processes = CouncilProcess::whereIn('id', $this->selectedProcesses)->get();

        foreach ($processes as $process) {
            $process->delete();
            ActivityLogger::deleted($process);
        }

        $this->selectedProcesses = [];
        $this->dispatch('toast', title: 'Processos', message: $processes->count().' processo(s) excluído(s) com sucesso.', variant: 'danger');
    }

    /**
     * Isolada num island (ver council-member-index.blade.php): precisa
     * estar acessível via $this-> porque islands não têm acesso a
     * variáveis de view vindas de fora deles, só a propriedades/métodos
     * do componente.
     */
    #[Computed]
    public function processes()
    {
        return $this->filteredProcesses()->orderByDesc('sei_number')->paginate(10);
    }

    #[Computed]
    public function allProcessesSelectedOnPage(): bool
    {
        return $this->processes->isNotEmpty() && array_diff($this->processes->pluck('id')->all(), $this->selectedProcesses) === [];
    }

    public function render()
    {
        return view('livewire.admin.council.council-member-index', [
            'editingProcess' => $this->editingProcessId ? CouncilProcess::find($this->editingProcessId) : null,
            'totalProcesses' => CouncilProcess::count(),
        ])->layout('layouts.admin', ['title' => 'Conselho Superior']);
    }
}
