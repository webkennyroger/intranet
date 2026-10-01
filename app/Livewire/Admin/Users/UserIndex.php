<?php

namespace App\Livewire\Admin\Users;

use App\Enums\UserStatus;
use App\Models\User;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class UserIndex extends Component
{
    use WithPagination;

    public ?int $editingUserId = null;

    #[On('user-saved')]
    public function refreshList(): void
    {
        // Triggers a re-render; no state to update beyond pagination's own refresh.
        $this->editingUserId = null;
    }

    public function edit(int $userId): void
    {
        $this->editingUserId = $userId;
    }

    public function closeForm(): void
    {
        $this->editingUserId = null;
    }

    public function delete(int $userId): void
    {
        $user = User::findOrFail($userId);

        abort_unless(auth()->user()->can('users.delete'), 403);

        if ($user->id === auth()->id()) {
            $this->addError('delete', 'Você não pode excluir a própria conta.');

            return;
        }

        if ($user->isSuperAdmin() && User::superAdministratorCount() <= 1) {
            $this->addError('delete', 'Não é possível excluir o único Super Administrador.');

            return;
        }

        $user->delete();
    }

    public function toggleBlock(int $userId): void
    {
        $user = User::findOrFail($userId);

        abort_unless(auth()->user()->can('users.manage'), 403);

        if ($user->id === auth()->id()) {
            $this->addError('toggleBlock', 'Você não pode bloquear a própria conta.');

            return;
        }

        if ($user->isActive() && $user->isSuperAdmin() && User::superAdministratorCount() <= 1) {
            $this->addError('toggleBlock', 'Não é possível bloquear o único Super Administrador.');

            return;
        }

        $user->update([
            'status' => $user->isActive() ? UserStatus::Blocked : UserStatus::Active,
        ]);
    }

    public function render()
    {
        return view('livewire.admin.users.user-index', [
            'users' => User::with('roles')->orderBy('name')->paginate(15),
            'editingUser' => $this->editingUserId ? User::find($this->editingUserId) : null,
        ]);
    }
}
