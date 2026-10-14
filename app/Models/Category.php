<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Categoria usada para agrupar os artigos do Mural (ex.: Avisos, Notícias).
 * Um artigo pode pertencer a várias categorias.
 */
class Category extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'description', 'color'];

    public function articles(): BelongsToMany
    {
        return $this->belongsToMany(Article::class);
    }

    /**
     * Versão clara (10% de opacidade) da cor da categoria, usada como fundo
     * do ícone/selo nos cards do admin — a cor cheia fica reservada pro
     * ícone/texto em si, igual ao mesmo truque já usado em SiteSetting.
     */
    public function lightColor(): string
    {
        return $this->color.'1a';
    }

    /**
     * Preto ou branco, o que tiver mais contraste sobre a cor da categoria —
     * usado nos selos de categoria com fundo sólido (Mural, Início).
     */
    public function readableTextColor(): string
    {
        return SiteSetting::readableTextFor($this->color);
    }
}
