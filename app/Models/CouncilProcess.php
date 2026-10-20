<?php

namespace App\Models;

use Database\Factories\CouncilProcessFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Processo SEI do Conselho Superior exibido na página pública, com o
 * número do processo abrindo o link externo do SEI quando cadastrado.
 */
class CouncilProcess extends Model
{
    /** @use HasFactory<CouncilProcessFactory> */
    use HasFactory;

    protected $fillable = ['sei_number', 'subject', 'assigned_to', 'interested_party', 'external_url', 'is_published'];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
        ];
    }
}
