<?php

namespace App\Models;

use Database\Factories\DocumentCategoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Pasta/categoria de Documentos (Resoluções, Contratos, Cartilhas etc.).
 * Gerenciável pelo admin na aba "Pastas" de Documentos — cada pasta tem
 * sua própria cor, usada nos selos e ícones de pasta no admin e no site.
 */
class DocumentCategory extends Model
{
    /** @use HasFactory<DocumentCategoryFactory> */
    use HasFactory;

    protected $table = 'categories';

    protected $fillable = ['name', 'slug', 'icon', 'color'];

    /**
     * Valor padrão de "type" pra linhas novas — não pode ser um listener de
     * "creating" porque seeders rodados com WithoutModelEvents (ex.:
     * DatabaseSeeder) desligam esse evento, e todo registro acabaria
     * gravado com o "default" da coluna em vez do tipo certo.
     */
    protected $attributes = ['type' => 'document'];

    /**
     * "categories" guarda as categorias de 4 módulos diferentes, distinguidos
     * pela coluna "type" — esse scope garante que este model só veja/crie
     * as do tipo "document".
     */
    protected static function booted(): void
    {
        static::addGlobalScope(fn ($query) => $query->where('type', 'document'));
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function lightColor(): string
    {
        return $this->color.'1a';
    }
}
