<?php

namespace App\Livewire\Admin\Roles;

use App\Services\ActivityLogger;
use App\Services\PermissionManager;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Spatie\Permission\Models\Role;

/**
 * Tela admin "Papéis & Permissões": grade de cards com os papéis de acesso
 * (os 3 do sistema + quaisquer papéis personalizados) e, abaixo, a Matriz de
 * Permissões do papel selecionado — uma linha por módulo real do portal, com
 * uma coluna de checkbox para cada ação (visualizar/criar/editar/excluir).
 */
class RoleIndex extends Component
{
    /**
     * Papéis que vêm com o sistema e não podem ser excluídos.
     */
    public const BUILT_IN_ROLES = ['Super Administrador', 'Administrador', 'Defensor'];

    /**
     * Papel com a matriz travada (sempre acesso total), pra ninguém se
     * trancar fora do próprio painel editando a própria permissão por engano.
     */
    public const LOCKED_ROLE = 'Super Administrador';

    public ?int $selectedRoleId = null;

    /** @var array<int, string> */
    public array $selectedPermissions = [];

    public bool $showRoleForm = false;

    public ?int $editingRoleId = null;

    #[Validate('required|string|max:255')]
    public string $roleName = '';

    #[Validate('nullable|string|max:255')]
    public string $roleDescription = '';

    public function mount(): void
    {
        $this->selectedRoleId = Role::orderBy('name')->value('id');
        $this->loadSelectedPermissions();
    }

    public function selectRole(int $roleId): void
    {
        $this->selectedRoleId = $roleId;
        $this->loadSelectedPermissions();
    }

    public function selectAll(): void
    {
        if ($this->matrixLocked()) {
            return;
        }

        $this->selectedPermissions = PermissionManager::inventory();
    }

    public function deselectAll(): void
    {
        if ($this->matrixLocked()) {
            return;
        }

        $this->selectedPermissions = [];
    }

    public function savePermissions(): void
    {
        abort_unless(auth()->user()->can('roles.manage'), 403);

        $role = $this->selectedRole();

        if (! $role || $this->matrixLocked()) {
            return;
        }

        $role->syncPermissions($this->selectedPermissions);
        ActivityLogger::updated($role, ['permissions' => ['old' => null, 'new' => count($this->selectedPermissions).' permissões concedidas']]);

        $this->dispatch('toast', title: 'Papel', message: "Permissões de \"{$role->name}\" salvas com sucesso.", variant: 'success');
    }

    public function matrixLocked(): bool
    {
        return $this->selectedRole()?->name === self::LOCKED_ROLE;
    }

    public function selectedRole(): ?Role
    {
        return $this->selectedRoleId ? Role::find($this->selectedRoleId) : null;
    }

    public function newRole(): void
    {
        abort_unless(auth()->user()->can('roles.manage'), 403);

        $this->reset(['editingRoleId', 'roleName', 'roleDescription']);
        $this->showRoleForm = true;
    }

    public function editRole(int $roleId): void
    {
        abort_unless(auth()->user()->can('roles.manage'), 403);

        $role = Role::findOrFail($roleId);

        $this->editingRoleId = $role->id;
        $this->roleName = $role->name;
        $this->roleDescription = (string) $role->description;
        $this->showRoleForm = true;
    }

    public function closeRoleForm(): void
    {
        $this->showRoleForm = false;
        $this->reset(['editingRoleId', 'roleName', 'roleDescription']);
    }

    public function saveRole(): void
    {
        abort_unless(auth()->user()->can('roles.manage'), 403);

        $this->validate([
            'roleName' => ['required', 'string', 'max:255', Rule::unique('roles', 'name')->ignore($this->editingRoleId)],
            'roleDescription' => 'nullable|string|max:255',
        ]);

        $data = [
            'name' => $this->roleName,
            'description' => $this->roleDescription !== '' ? $this->roleDescription : null,
        ];

        if ($this->editingRoleId) {
            $role = Role::findOrFail($this->editingRoleId);
            $changes = ActivityLogger::diff($role, $data);
            $role->update($data);
            if ($changes !== []) {
                ActivityLogger::updated($role, $changes);
            }
            $this->dispatch('toast', title: 'Papel', message: "Papel \"{$role->name}\" atualizado com sucesso.", variant: 'success');
        } else {
            $role = Role::create($data + ['guard_name' => 'web']);
            ActivityLogger::created($role);
            $this->selectedRoleId = $role->id;
            $this->loadSelectedPermissions();
            $this->dispatch('toast', title: 'Papel', message: "Papel \"{$role->name}\" criado com sucesso.", variant: 'success');
        }

        $this->closeRoleForm();
    }

    public function deleteRole(int $roleId): void
    {
        abort_unless(auth()->user()->can('roles.manage'), 403);

        $role = Role::findOrFail($roleId);

        if (in_array($role->name, self::BUILT_IN_ROLES, true)) {
            $this->addError('delete', 'Não é possível excluir um papel padrão do sistema.');

            return;
        }

        if ($role->users()->count() > 0) {
            $this->addError('delete', 'Não é possível excluir um papel com usuários atribuídos. Troque o papel desses usuários primeiro.');

            return;
        }

        $name = $role->name;
        ActivityLogger::deleted($role);
        $role->delete();
        $this->dispatch('toast', title: 'Papel', message: "Papel \"{$name}\" excluído com sucesso.", variant: 'danger');

        if ($this->selectedRoleId === $roleId) {
            $this->selectedRoleId = Role::orderBy('name')->value('id');
            $this->loadSelectedPermissions();
        }
    }

    private function loadSelectedPermissions(): void
    {
        $role = $this->selectedRole();
        $this->selectedPermissions = $role ? $role->permissions->pluck('name')->all() : [];
    }

    public function render()
    {
        return view('livewire.admin.roles.role-index', [
            'roles' => Role::withCount('users')->orderBy('name')->get(),
            'modules' => PermissionManager::modules(),
            'actionLabels' => [
                'view' => 'Visualizar',
                'create' => 'Criar',
                'update' => 'Editar',
                'delete' => 'Excluir',
            ],
        ])->layout('layouts.admin', ['title' => 'Papéis & Permissões']);
    }
}
