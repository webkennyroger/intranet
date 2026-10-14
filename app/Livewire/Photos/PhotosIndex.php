<?php

namespace App\Livewire\Photos;

use App\Models\PhotoAlbum;
use App\Models\Video;
use Livewire\Component;

/**
 * Página de Mídias: aba Fotos lista os álbuns publicados, do evento
 * mais recente para o mais antigo; aba Vídeos lista os vídeos
 * institucionais publicados, mais recentes primeiro.
 */
class PhotosIndex extends Component
{
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

    public function toggleVideoLike(int $videoId): void
    {
        $video = Video::findOrFail($videoId);
        $user = auth()->user();

        if ($video->isLikedBy($user)) {
            $video->likedByUsers()->detach($user->id);
        } else {
            $video->likedByUsers()->attach($user->id);
        }
    }

    public function incrementVideoViews(int $videoId): void
    {
        Video::findOrFail($videoId)->incrementViews();
    }

    public function render()
    {
        return view('livewire.photos.photos-index', [
            'albums' => PhotoAlbum::where('is_published', true)->withCount(['photos', 'likedByUsers'])->latest('event_date')->get(),
            'videos' => Video::where('is_published', true)->withCount('likedByUsers')->latest()->get(),
        ]);
    }
}
