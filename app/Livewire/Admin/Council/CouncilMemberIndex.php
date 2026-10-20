<?php

namespace App\Livewire\Admin\Council;

use App\Livewire\Concerns\ComputesWeeklyTrend;
use App\Models\CouncilMember;
use App\Models\CouncilProcess;
use App\Services\ActivityLogger;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Tela admin "Conselho Superior": gerencia os membros do conselho
 * (reordenação por arrastar-e-soltar, ativação/desativação, exclusão) e,
 * abaixo, os processos SEI do conselho (busca, publicar/despublicar,
 * exclusão) — tudo na mesma tela, mesmo item de menu.
 */
class CouncilMemberIndex extends Component
{
    use ComputesWeeklyTrend, WithPagination;

    // --- Membros ---

    public ?int $editingMemberId = null;

    /** @var array<int, int> */
    public array $selectedMembers = [];

    #[On('council-member-saved')]
    public function refreshMembers(): void
    {
        $this->editingMemberId = null;
        $this->dispatch('close-modal', 'council-member-form');
    }

    public function editMember(int $memberId): void
    {
        $this->editingMemberId = $memberId;
    }

    public function closeForm(): void
    {
        $this->editingMemberId = null;
    }

    public function toggleActive(int $memberId): void
    {
        $member = CouncilMember::findOrFail($memberId);
        $willBeActive = ! $member->is_active;
        $member->update(['is_active' => $willBeActive]);
        $willBeActive ? ActivityLogger::activated($member) : ActivityLogger::deactivated($member);
        $this->dispatch('toast', title: 'Conselho Superior', message: $willBeActive ? 'Membro ativado com sucesso.' : 'Membro desativado.', variant: $willBeActive ? 'success' : 'warning');
    }

    public function deleteMember(int $memberId): void
    {
        abort_unless(auth()->user()->can('council.delete'), 403);

        $member = CouncilMember::findOrFail($memberId);
        $member->delete();
        ActivityLogger::deleted($member);
        $this->dispatch('toast', title: 'Conselho Superior', message: 'Membro excluído com sucesso.', variant: 'danger');
    }

    public function toggleSelectAllMembersOnPage(): void
    {
        $pageIds = CouncilMember::orderBy('order')->pluck('id')->all();

        if (array_diff($pageIds, $this->selectedMembers) === []) {
            $this->selectedMembers = array_values(array_diff($this->selectedMembers, $pageIds));
        } else {
            $this->selectedMembers = array_values(array_unique(array_merge($this->selectedMembers, $pageIds)));
        }
    }

    public function deselectAllMembers(): void
    {
        $this->selectedMembers = [];
    }

    public function bulkActivate(): void
    {
        abort_unless(auth()->user()->can('council.update'), 403);

        $members = CouncilMember::whereIn('id', $this->selectedMembers)->where('is_active', false)->get();

        foreach ($members as $member) {
            $member->update(['is_active' => true]);
            ActivityLogger::activated($member);
        }

        $this->selectedMembers = [];
        $message = $members->isNotEmpty() ? $members->count().' membro(s) ativado(s) com sucesso.' : 'Nenhum membro inativo selecionado para ativar.';
        $this->dispatch('toast', title: 'Conselho Superior', message: $message, variant: $members->isNotEmpty() ? 'success' : 'warning');
    }

    public function bulkDeactivate(): void
    {
        abort_unless(auth()->user()->can('council.update'), 403);

        $members = CouncilMember::whereIn('id', $this->selectedMembers)->where('is_active', true)->get();

        foreach ($members as $member) {
            $member->update(['is_active' => false]);
            ActivityLogger::deactivated($member);
        }

        $this->selectedMembers = [];
        $message = $members->isNotEmpty() ? $members->count().' membro(s) desativado(s).' : 'Nenhum membro ativo selecionado.';
        $this->dispatch('toast', title: 'Conselho Superior', message: $message, variant: 'warning');
    }

    public function bulkDeleteMembers(): void
    {
        abort_unless(auth()->user()->can('council.delete'), 403);

        $members = CouncilMember::whereIn('id', $this->selectedMembers)->get();

        foreach ($members as $member) {
            $member->delete();
            ActivityLogger::deleted($member);
        }

        $this->selectedMembers = [];
        $this->dispatch('toast', title: 'Conselho Superior', message: $members->count().' membro(s) excluído(s) com sucesso.', variant: 'danger');
    }

    /**
     * @param  array<int, int>  $ids
     */
    public function reorder(array $ids): void
    {
        $existingCount = CouncilMember::whereIn('id', $ids)->count();

        // A lista arrastada pelo navegador pode não bater com o banco (ids
        // duplicados, removidos por outra aba, etc.) — essa checagem garante
        // que $ids é exatamente o conjunto atual de membros antes de aplicar
        // a nova ordem, evitando corromper o campo "order".
        abort_if($existingCount !== count(array_unique($ids)) || $existingCount !== CouncilMember::count(), 403);

        foreach ($ids as $position => $id) {
            CouncilMember::whereKey($id)->update(['order' => $position]);
        }
    }

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
        abort_unless(auth()->user()->can('council-processes.update'), 403);

        $process = CouncilProcess::findOrFail($processId);
        $willBePublished = ! $process->is_published;
        $process->update(['is_published' => $willBePublished]);
        $willBePublished ? ActivityLogger::activated($process) : ActivityLogger::deactivated($process);
        $this->dispatch('toast', title: 'Processo', message: $willBePublished ? 'Processo publicado com sucesso.' : 'Processo despublicado.', variant: $willBePublished ? 'success' : 'warning');
    }

    public function deleteProcess(int $processId): void
    {
        abort_unless(auth()->user()->can('council-processes.delete'), 403);

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
        abort_unless(auth()->user()->can('council-processes.update'), 403);

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
        abort_unless(auth()->user()->can('council-processes.update'), 403);

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
        abort_unless(auth()->user()->can('council-processes.delete'), 403);

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
        $members = CouncilMember::orderBy('order')->get();

        return view('livewire.admin.council.council-member-index', [
            'members' => $members,
            'allSelectedOnPage' => $members->isNotEmpty() && array_diff($members->pluck('id')->all(), $this->selectedMembers) === [],
            'editingMember' => $this->editingMemberId ? CouncilMember::find($this->editingMemberId) : null,
            'totalMembers' => CouncilMember::count(),
            'activeMembers' => CouncilMember::where('is_active', true)->count(),
            'editingProcess' => $this->editingProcessId ? CouncilProcess::find($this->editingProcessId) : null,
            'totalProcesses' => CouncilProcess::count(),
            'totalProcessesTrend' => $this->weeklyTrend(CouncilProcess::query(), 'created_at'),
            'publishedProcesses' => CouncilProcess::where('is_published', true)->count(),
            'publishedProcessesTrend' => $this->weeklyTrend(CouncilProcess::query()->where('is_published', true), 'created_at'),
            'draftProcesses' => CouncilProcess::where('is_published', false)->count(),
            'draftProcessesTrend' => $this->weeklyTrend(CouncilProcess::query()->where('is_published', false), 'created_at'),
        ])->layout('layouts.admin', ['title' => 'Conselho Superior']);
    }
}
