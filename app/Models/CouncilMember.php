<?php

namespace App\Models;

use Database\Factories\CouncilMemberFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Membro do Conselho Superior exibido na página pública do Conselho,
 * ordenado por `order` e podendo ser ativado/desativado sem apagar o registro.
 */
class CouncilMember extends Model
{
    /** @use HasFactory<CouncilMemberFactory> */
    use HasFactory;

    protected $fillable = ['name', 'role', 'photo_path', 'order', 'is_active'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
