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

    protected $fillable = ['name', 'slug', 'icon', 'color'];

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function lightColor(): string
    {
        return $this->color.'1a';
    }
}
