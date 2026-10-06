<?php

namespace App\Enums;

/**
 * Status possíveis de um usuário: ativo (pode logar normalmente) ou
 * bloqueado (acesso suspenso sem precisar excluir a conta).
 */
enum UserStatus: string
{
    case Active = 'active';
    case Blocked = 'blocked';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Ativo',
            self::Blocked => 'Bloqueado',
        };
    }
}
