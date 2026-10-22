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

    protected $table = 'categories';

    protected $fillable = ['name', 'slug', 'icon', 'color'];

    /**
     * Valor padrão de "type" pra linhas novas — não pode ser um listener de
     * "creating" porque seeders rodados com WithoutModelEvents (ex.:
     * DatabaseSeeder) desligam esse evento, e todo registro acabaria
     * gravado com o "default" da coluna em vez do tipo certo.
     */
    protected $attributes = ['type' => 'training'];

    /**
     * "categories" guarda as categorias de 4 módulos diferentes, distinguidos
     * pela coluna "type" — esse scope garante que este model só veja/crie
     * as do tipo "training".
     */
    protected static function booted(): void
    {
        static::addGlobalScope(fn ($query) => $query->where('type', 'training'));
    }

    public function trainings(): HasMany
    {
        return $this->hasMany(Training::class);
    }

    public function lightColor(): string
    {
        return $this->color.'1a';
    }
}
