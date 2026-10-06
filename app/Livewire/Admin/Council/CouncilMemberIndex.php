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

    #[On('council-member-saved')]
    public function refreshList(): void
    {
        $this->editingMemberId = null;
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
    }

    public function delete(int $memberId): void
    {
        abort_unless(auth()->user()->can('council.manage'), 403);

        $member = CouncilMember::findOrFail($memberId);
        $member->delete();
        ActivityLogger::deleted($member);
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
        return view('livewire.admin.council.council-member-index', [
            'members' => CouncilMember::orderBy('order')->get(),
            'editingMember' => $this->editingMemberId ? CouncilMember::find($this->editingMemberId) : null,
            'totalMembers' => CouncilMember::count(),
            'activeMembers' => CouncilMember::where('is_active', true)->count(),
        ])->layout('layouts.admin', ['title' => 'Conselho Superior']);
    }
}
