<?php

namespace App\Livewire\Admin\Photos;

use App\Models\Photo;
use App\Models\PhotoAlbum;
use App\Services\ActivityLogger;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Formulário admin de criação/edição de álbuns de fotos: dados do álbum e
 * upload de múltiplas fotos, além de remover fotos já cadastradas.
 */
class PhotoAlbumForm extends Component
{
    use WithFileUploads;

    public ?PhotoAlbum $album = null;

    #[Validate('required|string|max:255')]
    public string $title = '';

    #[Validate('nullable|string|max:1000')]
    public string $description = '';

    #[Validate('nullable|date')]
    public string $eventDate = '';

    public array $newPhotos = [];

    public bool $isPublished = true;

    public function mount(?PhotoAlbum $album = null): void
    {
        if ($album) {
            $this->album = $album;
            $this->title = $album->title;
            $this->description = (string) $album->description;
            $this->eventDate = $album->event_date?->format('Y-m-d') ?? '';
            $this->isPublished = $album->is_published;
        }
    }

    public function removePhoto(int $photoId): void
    {
        abort_unless(auth()->user()->can('content.delete'), 403);

        Photo::where('id', $photoId)->where('photo_album_id', $this->album?->id)->delete();
    }

    public function save(): void
    {
        abort_unless(auth()->user()->can($this->album ? 'content.update' : 'content.create'), 403);

        $this->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'eventDate' => 'nullable|date',
            'newPhotos.*' => 'image|max:4096',
        ]);

        $data = [
            'title' => $this->title,
            'description' => $this->description !== '' ? $this->description : null,
            'event_date' => $this->eventDate !== '' ? $this->eventDate : null,
            'is_published' => $this->isPublished,
        ];

        if ($this->album) {
            $changes = ActivityLogger::diff($this->album, $data);
            $this->album->update($data);
            if ($changes !== []) {
                ActivityLogger::updated($this->album, $changes);
            }
            $album = $this->album;
        } else {
            $album = PhotoAlbum::create($data);
            ActivityLogger::created($album);
        }

        // Continua a numeração a partir da última foto existente, para que
        // as novas entrem depois das já cadastradas em vez de embaralhar a ordem.
        $nextOrder = ($album->photos()->max('order') ?? 0) + 1;

        foreach ($this->newPhotos as $photo) {
            $album->photos()->create([
                'image_path' => $photo->store('photos', 'public'),
                'order' => $nextOrder++,
            ]);
        }

        // Define a primeira foto como capa automaticamente, só se o álbum
        // ainda não tiver uma — assim o admin não precisa escolher capa à mão.
        if (! $album->cover_image_path && ($coverPath = $album->photos()->first()?->image_path)) {
            $album->update(['cover_image_path' => $coverPath]);
        }

        $this->dispatch('photo-album-saved');
        $this->reset(['title', 'description', 'eventDate', 'newPhotos']);
        $this->isPublished = true;
    }

    public function render()
    {
        return view('livewire.admin.photos.photo-album-form');
    }
}
