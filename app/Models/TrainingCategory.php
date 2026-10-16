<?php

namespace App\Models;

use Database\Factories\TrainingCategoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Pasta/categoria de capacitações (ex.: Treinamento, Curso), usada para
 * agrupar e filtrar as capacitações exibidas na página pública.
 */
class TrainingCategory extends Model
{
    /** @use HasFactory<TrainingCategoryFactory> */
    use HasFactory;

    protected $fillable = ['name', 'slug', 'icon', 'color'];

    public function trainings(): HasMany
    {
        return $this->hasMany(Training::class);
    }

    public function lightColor(): string
    {
        return $this->color.'1a';
    }
}
