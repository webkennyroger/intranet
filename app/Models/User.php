<?php

namespace App\Models;

use App\Enums\UserStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

/**
 * Usuário (servidor) do sistema. Combina autenticação, papéis/permissões
 * via Spatie (HasRoles) e status ativo/bloqueado (UserStatus) para
 * controlar quem pode acessar e o que pode fazer.
 */
class User extends Authenticatable
{
    use HasFactory, HasRoles, Notifiable, SoftDeletes;

    protected $fillable = [
        'name', 'username', 'email', 'password', 'status', 'department', 'birth_date',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'status' => UserStatus::class,
            'birth_date' => 'date',
            'last_login_at' => 'datetime',
        ];
    }

    public function isActive(): bool
    {
        return $this->status === UserStatus::Active;
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole('Super Administrador');
    }

    public static function superAdministratorCount(): int
    {
        return static::role('Super Administrador')->count();
    }

    public function getAvatarInitials(): string
    {
        return strtoupper(mb_substr($this->name, 0, 2));
    }
}
