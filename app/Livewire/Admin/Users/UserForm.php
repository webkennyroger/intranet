<?php

namespace App\Livewire\Admin\Users;

use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Spatie\Permission\Models\Role;

/**
 * Formulário admin de criação/edição de usuários do sistema: dados
 * cadastrais, papel (role) de acesso e senha.
 */
class UserForm extends Component
{
    public ?User $user = null;

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('required|string|max:255')]
    public string $username = '';

    #[Validate('required|email|max:255')]
    public string $email = '';

    public string $password = '';

    public string $role = 'Administrador';

    public string $department = '';

    #[Validate('nullable|date')]
    public string $birthDate = '';

    public function mount(?User $user = null): void
    {
        if ($user) {
            $this->user = $user;
            $this->name = $user->name;
            $this->username = $user->username;
            $this->email = $user->email;
            $this->role = $user->getRoleNames()->first() ?? 'Administrador';
            $this->department = $user->department ?? '';
            $this->birthDate = $user->birth_date?->format('Y-m-d') ?? '';
        }
    }

    public function save(): void
    {
        abort_unless(auth()->user()->can($this->user ? 'users.update' : 'users.create'), 403);

        $rules = [
            'name' => 'required|string|max:255',
            'username' => ['required', 'string', 'max:255', Rule::unique('users', 'username')->ignore($this->user?->id)],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->user?->id)],
            'role' => ['required', Rule::in(Role::pluck('name'))],
            'department' => 'nullable|string|max:255',
            'birthDate' => 'nullable|date',
        ];

        if (! $this->user) {
            $rules['password'] = 'required|string|min:8';
        } elseif ($this->password !== '') {
            $rules['password'] = 'string|min:8';
        }

        $this->validate($rules);

        // Impede remover o papel de Super Administrador do último usuário
        // que o possui — sem essa trava, o sistema poderia ficar sem
        // nenhum Super Administrador capaz de gerenciar os demais usuários.
        if (
            $this->user
            && $this->user->isSuperAdmin()
            && $this->role !== 'Super Administrador'
            && User::superAdministratorCount() <= 1
        ) {
            $this->addError('role', 'Não é possível remover o papel do único Super Administrador.');

            return;
        }

        $loggableData = [
            'name' => $this->name,
            'username' => $this->username,
            'email' => $this->email,
            'department' => $this->department !== '' ? $this->department : null,
            'birth_date' => $this->birthDate !== '' ? $this->birthDate : null,
        ];

        $data = $loggableData;

        if ($this->password !== '') {
            $data['password'] = Hash::make($this->password);
        }

        if ($this->user) {
            $changes = ActivityLogger::diff($this->user, $loggableData);
            $previousRole = $this->user->getRoleNames()->first();

            $this->user->update($data);
            $this->user->syncRoles([$this->role]);

            if ($previousRole !== $this->role) {
                $changes['role'] = ['old' => $previousRole, 'new' => $this->role];
            }

            if ($changes !== []) {
                ActivityLogger::updated($this->user, $changes);
            }
            $this->dispatch('toast', title: 'Usuário', message: "Usuário {$this->name} atualizado com sucesso.", variant: 'success');
        } else {
            $user = User::create($data + ['password' => Hash::make($this->password)]);
            $user->assignRole($this->role);
            ActivityLogger::created($user);
            $this->dispatch('toast', title: 'Usuário', message: "Usuário {$this->name} criado com sucesso.", variant: 'success');
        }

        $this->dispatch('user-saved');
        $this->reset(['name', 'username', 'email', 'password', 'department', 'birthDate']);
        $this->role = 'Administrador';
    }

    public function render()
    {
        return view('livewire.admin.users.user-form', [
            'roles' => Role::orderBy('name')->get(),
        ]);
    }
}
