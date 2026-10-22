<?php

namespace App\Models;

use Database\Factories\EventCategoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Categoria/tipo rápido de evento: aparece como um chip arrastável na
 * barra lateral do calendário admin (ver EventIndex) — soltar um desses
 * sobre um dia cria um evento com esse nome e cor, sem abrir o formulário
 * primeiro. Independente dos eventos já criados: excluir uma categoria
 * não afeta a cor dos eventos que já copiaram seu hex no momento da
 * criação.
 */
class EventCategory extends Model
{
    /** @use HasFactory<EventCategoryFactory> */
    use HasFactory;

    protected $table = 'categories';

    protected $fillable = ['name', 'color', 'order'];

    /**
     * Valor padrão de "type" pra linhas novas — não pode ser um listener de
     * "creating" porque seeders rodados com WithoutModelEvents (ex.:
     * DatabaseSeeder) desligam esse evento, e todo registro acabaria
     * gravado com o "default" da coluna em vez do tipo certo.
     */
    protected $attributes = ['type' => 'event'];

    /**
     * "categories" guarda as categorias de 4 módulos diferentes, distinguidos
     * pela coluna "type" — esse scope garante que este model só veja/crie
     * as do tipo "event".
     */
    protected static function booted(): void
    {
        static::addGlobalScope(fn ($query) => $query->where('type', 'event'));
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }
}
