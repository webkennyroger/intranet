<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Atalho para um serviço/sistema externo, exibido na sidebar ou na página
 * de serviços enquanto estiver `is_active`, respeitando a ordem definida.
 */
class ServiceLink extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'icon', 'url', 'order', 'is_active'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
