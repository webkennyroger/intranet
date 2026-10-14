<?php

namespace App\Livewire\Home;

use App\Models\Article;
use App\Models\BirthdayBanner;
use App\Models\Event;
use App\Models\PhotoAlbum;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;

/**
 * Página Início: portal de notícias da intranet. Reúne a manchete e os
 * destaques do Mural, o feed de últimas notícias, a galeria de álbuns de
 * fotos, o próximo evento publicado e os aniversariantes do mês — tudo
 * puxado dos mesmos dados geridos no admin, sem conteúdo fixo/fake.
 */
class HomeIndex extends Component
{
    public function toggleLike(int $articleId): void
    {
        $article = Article::findOrFail($articleId);
        $user = auth()->user();

        if ($article->isLikedBy($user)) {
            $article->likedByUsers()->detach($user->id);
        } else {
            $article->likedByUsers()->attach($user->id);
        }
    }

    public function render()
    {
        $articles = Article::published()
            ->with(['categories', 'author'])
            ->latest('published_at')
            ->take(7)
            ->get();

        $headline = $articles->first();
        $latestNews = $articles->slice(4, 3);

        $photoAlbums = PhotoAlbum::where('is_published', true)
            ->withCount('photos')
            ->latest('event_date')
            ->take(12)
            ->get();

        $nextEvent = Event::where('is_published', true)
            ->where('starts_at', '>=', now())
            ->orderBy('starts_at')
            ->first();

        // "Em Destaque": sempre 1 de cada origem (Notícia, Mural, próximo
        // Evento e álbum de Fotos mais recente), não as últimas matérias
        // quaisquer — pedido explícito pra misturar essas seções no mesmo
        // card, cada uma com sua própria foto.
        $destaqueNews = Article::published()
            ->with(['categories', 'author'])
            ->whereHas('categories', fn ($q) => $q->where('slug', 'noticia'))
            ->latest('published_at')
            ->first();

        $destaqueMural = Article::published()
            ->with(['categories', 'author'])
            ->whereHas('categories', fn ($q) => $q->where('slug', 'mural'))
            ->where('id', '!=', $headline?->id)
            ->latest('published_at')
            ->first();

        $destaqueAlbum = $photoAlbums->first();

        $highlighted = collect([
            $destaqueNews ? [
                'label' => 'Notícia',
                'title' => $destaqueNews->title,
                'url' => route('mural.show', $destaqueNews),
                'image' => $destaqueNews->featuredImageUrl(),
                'icon' => $destaqueNews->cover_icon,
            ] : null,
            $destaqueMural ? [
                'label' => 'Mural',
                'title' => $destaqueMural->title,
                'url' => route('mural.show', $destaqueMural),
                'image' => $destaqueMural->featuredImageUrl(),
                'icon' => $destaqueMural->cover_icon,
            ] : null,
            $nextEvent ? [
                'label' => 'Evento',
                'title' => $nextEvent->title,
                'url' => route('events.index'),
                'image' => $nextEvent->coverImageUrl(),
                'icon' => 'calendar-days',
            ] : null,
            $destaqueAlbum ? [
                'label' => 'Fotos',
                'title' => $destaqueAlbum->title,
                'url' => route('photos.show', $destaqueAlbum),
                'image' => $destaqueAlbum->cover_image_path ? Storage::url($destaqueAlbum->cover_image_path) : null,
                'icon' => 'images',
            ] : null,
        ])->filter()->values();

        $birthdayBanner = BirthdayBanner::where('month', now()->month)
            ->where('is_published', true)
            ->first();

        return view('livewire.home.home-index', [
            'headline' => $headline,
            'highlighted' => $highlighted,
            'latestNews' => $latestNews,
            'photoAlbumSlides' => $this->photoAlbumSlides($photoAlbums),
            'nextEvent' => $nextEvent,
            'birthdayBanner' => $birthdayBanner,
        ])->layout('layouts.app', ['title' => 'Início']);
    }

    /**
     * Agrupa os álbuns em "slides" de 4 pro carrossel da Galeria Multimídia
     * — juntar o resto num slide anterior (como antes) ainda deixava uma
     * linha furada, com espaço em branco sobrando. Em vez disso, só com 4
     * ou menos álbuns (nenhuma página "cheia" pra comparar) mostra todos
     * num slide só; havendo mais que isso, mostra só os múltiplos de 4 — o
     * resto (sempre < 4) fica de fora do carrossel da Início (continua
     * acessível em "Ver todas as galerias"), garantindo que toda página
     * exibida vem completa, sem buraco.
     *
     * @param  Collection<int, PhotoAlbum>  $albums
     * @return Collection<int, Collection<int, PhotoAlbum>>
     */
    private function photoAlbumSlides($albums)
    {
        $total = $albums->count();

        if ($total <= 4) {
            return collect([$albums]);
        }

        return $albums->take(intdiv($total, 4) * 4)->chunk(4)->values();
    }
}
