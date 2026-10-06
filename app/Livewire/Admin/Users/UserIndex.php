<?php

namespace App\Livewire\Admin\Users;

use App\Enums\UserStatus;
use App\Models\User;
use App\Services\ActivityLogger;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Tela admin "Usuários": lista os usuários cadastrados, com busca, troca
 * de papel (role), bloqueio/desbloqueio e exclusão.
 */
class UserIndex extends Component
{
    use WithPagination;

    public ?int $editingUserId = null;

    public string $search = '';

    /** @var array<int, string> */
    public array $roleChoice = [];

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

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

    public function changeRole(int $userId): void
    {
        abort_unless(auth()->user()->can('users.manage'), 403);

        $user = User::findOrFail($userId);
        $currentRole = $user->getRoleNames()->first();
        $newRole = $this->roleChoice[$userId] ?? null;

        if (! in_array($newRole, ['Super Administrador', 'Administrador', 'Defensor'], true) || $newRole === $currentRole) {
            return;
        }

        // Mantém sempre pelo menos um Super Administrador no sistema: sem
        // essa trava, o próprio painel ficaria sem ninguém com permissão
        // para gerenciar usuários e papéis.
        if ($user->isSuperAdmin() && $newRole !== 'Super Administrador' && User::superAdministratorCount() <= 1) {
            $this->addError('role', 'Não é possível remover o papel do único Super Administrador.');
            $this->roleChoice[$userId] = $currentRole;

            return;
        }

        $user->syncRoles([$newRole]);
        ActivityLogger::updated($user, ['role' => ['old' => $currentRole, 'new' => $newRole]]);
    }

    public function delete(int $userId): void
    {
        $user = User::findOrFail($userId);

        abort_unless(auth()->user()->can('users.delete'), 403);

        if ($user->id === auth()->id()) {
            $this->addError('delete', 'Você não pode excluir a própria conta.');

            return;
        }

        // Mesma regra de proteção: não deixa excluir o único Super
        // Administrador restante, senão o sistema perde quem pode gerenciar os usuários.
        if ($user->isSuperAdmin() && User::superAdministratorCount() <= 1) {
            $this->addError('delete', 'Não é possível excluir o único Super Administrador.');

            return;
        }

        $user->delete();
        ActivityLogger::deleted($user);
    }

    public function toggleBlock(int $userId): void
    {
        $user = User::findOrFail($userId);

        abort_unless(auth()->user()->can('users.manage'), 403);

        if ($user->id === auth()->id()) {
            $this->addError('toggleBlock', 'Você não pode bloquear a própria conta.');

            return;
        }

        // Mesma regra de proteção: não deixa bloquear o único Super
        // Administrador ativo, senão o sistema perde quem pode gerenciar os usuários.
        if ($user->isActive() && $user->isSuperAdmin() && User::superAdministratorCount() <= 1) {
            $this->addError('toggleBlock', 'Não é possível bloquear o único Super Administrador.');

            return;
        }

        $willBeActive = ! $user->isActive();

        $user->update([
            'status' => $willBeActive ? UserStatus::Active : UserStatus::Blocked,
        ]);

        $willBeActive ? ActivityLogger::activated($user) : ActivityLogger::deactivated($user);
    }

    public function render()
    {
        $users = User::with('roles')
            ->when($this->search !== '', fn ($query) => $query->where(fn ($query) => $query
                ->where('name', 'like', "%{$this->search}%")
                ->orWhere('email', 'like', "%{$this->search}%")))
            ->orderBy('name')
            ->paginate(10);

        foreach ($users as $user) {
            $this->roleChoice[$user->id] ??= $user->getRoleNames()->first();
        }

        return view('livewire.admin.users.user-index', [
            'users' => $users,
            'editingUser' => $this->editingUserId ? User::find($this->editingUserId) : null,
        ])->layout('layouts.admin', ['title' => 'Usuários']);
    }
}
