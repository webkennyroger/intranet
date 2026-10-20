<?php

namespace App\Livewire\Site\Home;

use App\Models\Article;
use App\Models\BirthdayBanner;
use App\Models\Event;
use App\Models\PhotoAlbum;
use App\Models\Video;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;

/**
 * Página Início: portal de notícias da intranet. Reúne a manchete e os
 * destaques do Mural, o feed de últimas notícias, a galeria de álbuns de
 * fotos e vídeos, o próximo evento publicado e os aniversariantes do mês —
 * tudo puxado dos mesmos dados geridos no admin, sem conteúdo fixo/fake.
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

    public function toggleAlbumLike(int $albumId): void
    {
        $album = PhotoAlbum::findOrFail($albumId);
        $user = auth()->user();

        if ($album->isLikedBy($user)) {
            $album->likedByUsers()->detach($user->id);
        } else {
            $album->likedByUsers()->attach($user->id);
        }
    }

    public function render()
    {
        $articles = Article::published()
            ->with(['categories', 'author'])
            ->latest('published_at')
            ->take(17)
            ->get();

        $headline = $articles->first();

        $photoAlbums = PhotoAlbum::where('is_published', true)
            ->withCount('photos')
            ->latest('event_date')
            ->take(12)
            ->get();

        $videos = Video::where('is_published', true)
            ->latest()
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

        // Artigos já usados no "Em Destaque" não aparecem de novo em
        // "Últimas Notícias", senão a mesma matéria mostra duplicada na
        // página.
        $highlightedArticleIds = collect([$headline?->id, $destaqueNews?->id, $destaqueMural?->id])->filter()->values();
        $latestNews = $articles->reject(fn (Article $article) => $highlightedArticleIds->contains($article->id))->values();

        $highlighted = collect([
            $destaqueNews ? [
                'label' => 'Notícia',
                'title' => $destaqueNews->title,
                'url' => route('mural.show', $destaqueNews),
                'image' => $destaqueNews->featuredImageUrl(),
                'icon' => $destaqueNews->cover_icon,
                'date' => $destaqueNews->published_at,
                'color' => $destaqueNews->categories->first()?->color ?? '#0C4A34',
                'views' => $destaqueNews->views_count,
                'likes' => $destaqueNews->likedByUsers()->count(),
                'isLiked' => $destaqueNews->isLikedBy(auth()->user()),
                'likeId' => $destaqueNews->id,
            ] : null,
            $destaqueMural ? [
                'label' => 'Mural',
                'title' => $destaqueMural->title,
                'url' => route('mural.show', $destaqueMural),
                'image' => $destaqueMural->featuredImageUrl(),
                'icon' => $destaqueMural->cover_icon,
                'date' => $destaqueMural->published_at,
                'color' => $destaqueMural->categories->first()?->color ?? '#0C4A34',
                'views' => $destaqueMural->views_count,
                'likes' => $destaqueMural->likedByUsers()->count(),
                'isLiked' => $destaqueMural->isLikedBy(auth()->user()),
                'likeId' => $destaqueMural->id,
            ] : null,
            $nextEvent ? [
                'label' => 'Evento',
                'title' => $nextEvent->title,
                'url' => route('events.index'),
                'image' => $nextEvent->coverImageUrl(),
                'icon' => 'calendar-days',
                'date' => $nextEvent->starts_at,
                'color' => '#DC2626',
                'views' => null,
                'likes' => null,
                'isLiked' => false,
                'likeId' => null,
            ] : null,
            $destaqueAlbum ? [
                'label' => 'Fotos',
                'title' => $destaqueAlbum->title,
                'url' => route('photos.show', $destaqueAlbum),
                'image' => $destaqueAlbum->cover_image_path ? Storage::url($destaqueAlbum->cover_image_path) : null,
                'icon' => 'images',
                'date' => $destaqueAlbum->event_date,
                'color' => '#2563EB',
                'views' => $destaqueAlbum->views_count,
                'likes' => $destaqueAlbum->likedByUsers()->count(),
                'isLiked' => $destaqueAlbum->isLikedBy(auth()->user()),
                'likeId' => $destaqueAlbum->id,
            ] : null,
        ])->filter()->values();

        $birthdayBanner = BirthdayBanner::where('month', now()->month)
            ->where('is_published', true)
            ->first();

        return view('livewire.site.home.home-index', [
            'headline' => $headline,
            'highlighted' => $highlighted,
            'latestNewsSlides' => $this->chunkIntoSlides($latestNews),
            'gallerySlides' => $this->chunkIntoSlides($this->galleryItems($photoAlbums, $videos)),
            'nextEvent' => $nextEvent,
            'birthdayBanner' => $birthdayBanner,
        ])->layout('layouts.app', ['title' => 'Início']);
    }

    /**
     * Junta álbuns de fotos e vídeos num único feed pra Galeria Multimídia,
     * cada item já normalizado (type/title/description/cover/...) pra a view
     * não precisar diferenciar PhotoAlbum de Video — ordenado do mais
     * recente pro mais antigo, misturando os dois tipos pela data.
     *
     * @param  Collection<int, PhotoAlbum>  $albums
     * @param  Collection<int, Video>  $videos
     * @return Collection<int, array>
     */
    private function galleryItems(Collection $albums, Collection $videos): Collection
    {
        $albumItems = $albums->map(fn (PhotoAlbum $album) => [
            'type' => 'album',
            'id' => $album->id,
            'title' => $album->title,
            'description' => $album->description,
            'cover' => $album->cover_image_path ? Storage::url($album->cover_image_path) : null,
            'url' => route('photos.show', $album),
            'embedUrl' => null,
            'countLabel' => $album->photos_count.' '.($album->photos_count === 1 ? 'Foto' : 'Fotos'),
            'date' => $album->event_date,
        ]);

        $videoItems = $videos->map(fn (Video $video) => [
            'type' => 'video',
            'id' => $video->id,
            'title' => $video->title,
            'description' => $video->description,
            'cover' => $video->thumbnailUrl(),
            'url' => null,
            'embedUrl' => $video->embedUrl(),
            'countLabel' => 'Vídeo',
            'date' => $video->created_at,
        ]);

        return $albumItems->concat($videoItems)
            ->sortByDesc('date')
            ->values();
    }

    /**
     * Agrupa uma coleção em "slides" de 4 pro carrossel (Últimas Notícias e
     * Galeria Multimídia) — juntar o resto num slide anterior ainda deixava
     * uma linha furada, com espaço em branco sobrando. Em vez disso, só com
     * 4 ou menos itens (nenhuma página "cheia" pra comparar) mostra todos
     * num slide só; havendo mais que isso, mostra só os múltiplos de 4 — o
     * resto (sempre < 4) fica de fora do carrossel da Início, garantindo que
     * toda página exibida vem completa, sem buraco.
     *
     * @param  Collection<int, mixed>  $items
     * @return Collection<int, Collection<int, mixed>>
     */
    private function chunkIntoSlides(Collection $items): Collection
    {
        $total = $items->count();

        if ($total <= 4) {
            return collect([$items]);
        }

        return $items->take(intdiv($total, 4) * 4)->chunk(4)->values();
    }
}
