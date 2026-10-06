<?php

namespace App\Models;

use Database\Factories\TrainingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Capacitação/curso divulgado aos usuários, com link externo de inscrição
 * e carga horária, visível quando publicada.
 */
class Training extends Model
{
    /** @use HasFactory<TrainingFactory> */
    use HasFactory;

    protected $fillable = ['title', 'description', 'external_url', 'workload_hours', 'starts_at', 'is_published'];

    protected function casts(): array
    {
        return [
            'starts_at' => 'date',
            'is_published' => 'boolean',
        ];
    }
}
