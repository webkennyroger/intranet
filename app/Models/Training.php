<?php

namespace App\Models;

use Database\Factories\TrainingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Capacitação/curso divulgado aos usuários, com um ou mais links externos
 * de acesso/inscrição e carga horária, visível quando publicada.
 */
class Training extends Model
{
    /** @use HasFactory<TrainingFactory> */
    use HasFactory;

    protected $fillable = ['training_category_id', 'title', 'description', 'links', 'workload_hours', 'starts_at', 'is_published'];

    public function category(): BelongsTo
    {
        return $this->belongsTo(TrainingCategory::class, 'training_category_id');
    }

    protected function casts(): array
    {
        return [
            'links' => 'array',
            'starts_at' => 'date',
            'is_published' => 'boolean',
        ];
    }
}
