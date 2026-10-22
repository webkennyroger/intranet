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

    /**
     * Valor padrão de "type" pra linhas novas — não pode ser um listener de
     * "creating" porque seeders rodados com WithoutModelEvents (ex.:
     * DatabaseSeeder) desligam esse evento, e todo registro acabaria
     * gravado com o "default" da coluna em vez do tipo certo.
     */
    protected $attributes = ['type' => 'article'];

    /**
     * "categories" guarda as categorias de 4 módulos diferentes (artigos,
     * documentos, capacitações, eventos), distinguidos pela coluna "type" —
     * esse scope garante que este model só veja/crie as do tipo "article".
     */
    protected static function booted(): void
    {
        static::addGlobalScope(fn ($query) => $query->where('type', 'article'));
    }

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
