<?php

namespace App\Livewire\Admin\Users;

use App\Enums\UserStatus;
use App\Models\User;
use App\Services\ActivityLogger;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;

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

    /** @var array<int, int> */
    public array $selected = [];

    public function updatedSearch(): void
    {
        $this->resetPage();
        $this->selected = [];
    }

    #[On('user-saved')]
    public function refreshList(): void
    {
        $this->editingUserId = null;
        $this->dispatch('close-modal', 'user-form');
    }

    public function edit(int $userId): void
    {
        $this->editingUserId = $userId;
    }

    public function changeRole(int $userId): void
    {
        abort_unless(auth()->user()->can('users.update'), 403);

        $user = User::findOrFail($userId);
        $currentRole = $user->getRoleNames()->first();
        $newRole = $this->roleChoice[$userId] ?? null;

        if (! Role::where('name', $newRole)->exists() || $newRole === $currentRole) {
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
        $this->dispatch('toast', title: 'Usuário', message: "Papel de {$user->name} alterado para {$newRole}.", variant: 'success');
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

        $name = $user->name;
        $user->delete();
        ActivityLogger::deleted($user);
        $this->dispatch('toast', title: 'Usuário', message: "Usuário {$name} excluído com sucesso.", variant: 'danger');
    }

    public function toggleBlock(int $userId): void
    {
        $user = User::findOrFail($userId);

        abort_unless(auth()->user()->can('users.update'), 403);

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
        $this->dispatch('toast', title: 'Usuário', message: $willBeActive ? "Usuário {$user->name} desbloqueado." : "Usuário {$user->name} bloqueado.", variant: $willBeActive ? 'success' : 'warning');
    }

    /**
     * IDs da página atual, respeitando a busca ativa — usado tanto na
     * listagem principal quanto para "selecionar todos nesta página".
     */
    private function currentPageIds(): array
    {
        return User::when($this->search !== '', fn ($query) => $query->where(fn ($query) => $query
            ->where('name', 'like', "%{$this->search}%")
            ->orWhere('email', 'like', "%{$this->search}%")))
            ->orderBy('name')
            ->paginate(10)
            ->pluck('id')
            ->all();
    }

    public function toggleSelectAllOnPage(): void
    {
        $pageIds = $this->currentPageIds();

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

    /**
     * Bloqueia os usuários selecionados que ainda estão ativos — pula a
     * própria conta e o último Super Administrador ativo (mesmas regras do
     * bloqueio individual, ver toggleBlock()), em vez de travar a ação
     * inteira por causa de um item inválido na seleção.
     */
    public function bulkBlock(): void
    {
        abort_unless(auth()->user()->can('users.update'), 403);

        $blocked = 0;
        $skipped = 0;

        foreach (User::whereIn('id', $this->selected)->get() as $user) {
            if (! $user->isActive()) {
                continue;
            }

            if ($user->id === auth()->id() || ($user->isSuperAdmin() && User::superAdministratorCount() <= 1)) {
                $skipped++;

                continue;
            }

            $user->update(['status' => UserStatus::Blocked]);
            ActivityLogger::deactivated($user);
            $blocked++;
        }

        $this->selected = [];
        $message = "{$blocked} usuário(s) bloqueado(s).".($skipped > 0 ? " {$skipped} ignorado(s) (própria conta ou único Super Administrador)." : '');
        $this->dispatch('toast', title: 'Usuários', message: $message, variant: 'warning');
    }

    public function bulkUnblock(): void
    {
        abort_unless(auth()->user()->can('users.update'), 403);

        $users = User::whereIn('id', $this->selected)->where('status', UserStatus::Blocked)->get();

        foreach ($users as $user) {
            $user->update(['status' => UserStatus::Active]);
            ActivityLogger::activated($user);
        }

        $this->selected = [];
        $message = $users->isNotEmpty() ? $users->count().' usuário(s) desbloqueado(s).' : 'Nenhum usuário bloqueado selecionado.';
        $this->dispatch('toast', title: 'Usuários', message: $message, variant: $users->isNotEmpty() ? 'success' : 'warning');
    }

    /**
     * Exclui os usuários selecionados, pulando a própria conta e o último
     * Super Administrador restante (mesmas regras do delete() individual)
     * em vez de cancelar a ação inteira.
     */
    public function bulkDelete(): void
    {
        abort_unless(auth()->user()->can('users.delete'), 403);

        $deleted = 0;
        $skipped = 0;

        foreach (User::whereIn('id', $this->selected)->get() as $user) {
            if ($user->id === auth()->id() || ($user->isSuperAdmin() && User::superAdministratorCount() <= 1)) {
                $skipped++;

                continue;
            }

            $user->delete();
            ActivityLogger::deleted($user);
            $deleted++;
        }

        $this->selected = [];
        $message = "{$deleted} usuário(s) excluído(s).".($skipped > 0 ? " {$skipped} ignorado(s) (própria conta ou único Super Administrador)." : '');
        $this->dispatch('toast', title: 'Usuários', message: $message, variant: 'danger');
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
            'allSelectedOnPage' => $users->isNotEmpty() && array_diff($users->pluck('id')->all(), $this->selected) === [],
            'editingUser' => $this->editingUserId ? User::find($this->editingUserId) : null,
            'roles' => Role::orderBy('name')->get(),
        ])->layout('layouts.admin', ['title' => 'Usuários']);
    }
}
