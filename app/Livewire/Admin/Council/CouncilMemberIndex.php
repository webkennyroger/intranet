<?php

namespace App\Livewire\Admin\Council;

use App\Models\CouncilMember;
use App\Services\ActivityLogger;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Tela admin "Conselho Superior": lista os membros do conselho, com
 * reordenação via drag-and-drop, ativação/desativação e exclusão.
 */
class CouncilMemberIndex extends Component
{
    public ?int $editingMemberId = null;

    /** @var array<int, int> */
    public array $selected = [];

    #[On('council-member-saved')]
    public function refreshList(): void
    {
        $this->editingMemberId = null;
        $this->dispatch('close-modal', 'council-member-form');
    }

    public function edit(int $memberId): void
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

    public function delete(int $memberId): void
    {
        abort_unless(auth()->user()->can('council.delete'), 403);

        $member = CouncilMember::findOrFail($memberId);
        $member->delete();
        ActivityLogger::deleted($member);
        $this->dispatch('toast', title: 'Conselho Superior', message: 'Membro excluído com sucesso.', variant: 'danger');
    }

    public function toggleSelectAllOnPage(): void
    {
        $pageIds = CouncilMember::orderBy('order')->pluck('id')->all();

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

    public function bulkActivate(): void
    {
        abort_unless(auth()->user()->can('council.update'), 403);

        $members = CouncilMember::whereIn('id', $this->selected)->where('is_active', false)->get();

        foreach ($members as $member) {
            $member->update(['is_active' => true]);
            ActivityLogger::activated($member);
        }

        $this->selected = [];
        $message = $members->isNotEmpty() ? $members->count().' membro(s) ativado(s) com sucesso.' : 'Nenhum membro inativo selecionado para ativar.';
        $this->dispatch('toast', title: 'Conselho Superior', message: $message, variant: $members->isNotEmpty() ? 'success' : 'warning');
    }

    public function bulkDeactivate(): void
    {
        abort_unless(auth()->user()->can('council.update'), 403);

        $members = CouncilMember::whereIn('id', $this->selected)->where('is_active', true)->get();

        foreach ($members as $member) {
            $member->update(['is_active' => false]);
            ActivityLogger::deactivated($member);
        }

        $this->selected = [];
        $message = $members->isNotEmpty() ? $members->count().' membro(s) desativado(s).' : 'Nenhum membro ativo selecionado.';
        $this->dispatch('toast', title: 'Conselho Superior', message: $message, variant: 'warning');
    }

    public function bulkDelete(): void
    {
        abort_unless(auth()->user()->can('council.delete'), 403);

        $members = CouncilMember::whereIn('id', $this->selected)->get();

        foreach ($members as $member) {
            $member->delete();
            ActivityLogger::deleted($member);
        }

        $this->selected = [];
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

    public function render()
    {
        $members = CouncilMember::orderBy('order')->get();

        return view('livewire.admin.council.council-member-index', [
            'members' => $members,
            'allSelectedOnPage' => $members->isNotEmpty() && array_diff($members->pluck('id')->all(), $this->selected) === [],
            'editingMember' => $this->editingMemberId ? CouncilMember::find($this->editingMemberId) : null,
            'totalMembers' => CouncilMember::count(),
            'activeMembers' => CouncilMember::where('is_active', true)->count(),
        ])->layout('layouts.admin', ['title' => 'Conselho Superior']);
    }
}
