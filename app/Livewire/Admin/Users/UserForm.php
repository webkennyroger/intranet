<?php

namespace App\Livewire\Admin\Users;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Validate;
use Livewire\Component;

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

    #[Validate('required|in:Super Administrador,Administrador,Gestor,Editor')]
    public string $role = 'Editor';

    public string $department = '';

    public function mount(?User $user = null): void
    {
        if ($user) {
            $this->user = $user;
            $this->name = $user->name;
            $this->username = $user->username;
            $this->email = $user->email;
            $this->role = $user->getRoleNames()->first() ?? 'Editor';
            $this->department = $user->department ?? '';
        }
    }

    public function save(): void
    {
        $rules = [
            'name' => 'required|string|max:255',
            'username' => ['required', 'string', 'max:255', Rule::unique('users', 'username')->ignore($this->user?->id)],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->user?->id)],
            'role' => 'required|in:Super Administrador,Administrador,Gestor,Editor',
            'department' => $this->role === 'Gestor' ? 'required|string|max:255' : 'nullable|string|max:255',
        ];

        if (! $this->user) {
            $rules['password'] = 'required|string|min:8';
        } elseif ($this->password !== '') {
            $rules['password'] = 'string|min:8';
        }

        $this->validate($rules);

        if (
            $this->user
            && $this->user->isSuperAdmin()
            && $this->role !== 'Super Administrador'
            && User::superAdministratorCount() <= 1
        ) {
            $this->addError('role', 'Não é possível remover o papel do único Super Administrador.');

            return;
        }

        $data = [
            'name' => $this->name,
            'username' => $this->username,
            'email' => $this->email,
            'department' => $this->role === 'Gestor' ? $this->department : null,
        ];

        if ($this->password !== '') {
            $data['password'] = Hash::make($this->password);
        }

        if ($this->user) {
            $this->user->update($data);
            $this->user->syncRoles([$this->role]);
        } else {
            $user = User::create($data + ['password' => Hash::make($this->password)]);
            $user->assignRole($this->role);
        }

        $this->dispatch('user-saved');
        $this->reset(['name', 'username', 'email', 'password', 'department']);
        $this->role = 'Editor';
    }

    public function render()
    {
        return view('livewire.admin.users.user-form');
    }
}
