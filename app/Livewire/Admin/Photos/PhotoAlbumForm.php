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

    /** @var array<int, int> */
    public array $selectedPhotoIds = [];

    public function mount(?PhotoAlbum $album = null): void
    {
        if ($album) {
            $this->album = $album;
            $this->title = $album->title;
            $this->description = (string) $album->description;
            $this->eventDate = $album->event_date?->format('Y-m-d') ?? '';
        }
    }

    public function removePhoto(int $photoId): void
    {
        abort_unless(auth()->user()->can('photos.delete'), 403);

        $this->deletePhotos([$photoId]);
    }

    /**
     * Exclui de uma vez todas as fotos marcadas na grade "Fotos do álbum"
     * — sem isso o admin precisava clicar no "×" foto por foto, inviável
     * num álbum com dezenas/centenas de fotos.
     */
    public function removeSelectedPhotos(): void
    {
        abort_unless(auth()->user()->can('photos.delete'), 403);

        $this->deletePhotos($this->selectedPhotoIds);
        $this->selectedPhotoIds = [];
    }

    /**
     * Marca/desmarca de uma vez todas as fotos já salvas do álbum.
     */
    public function toggleSelectAllPhotos(): void
    {
        $allIds = $this->album?->photos->pluck('id')->all() ?? [];

        $this->selectedPhotoIds = count($this->selectedPhotoIds) === count($allIds) ? [] : $allIds;
    }

    /**
     * Remove as fotos informadas (restrito a este álbum) e, se a capa do
     * álbum estava entre as apagadas, reatribui pra próxima foto que
     * sobrou — sem isso o álbum ficaria com uma capa quebrada/inexistente.
     *
     * @param  array<int, int>  $photoIds
     */
    private function deletePhotos(array $photoIds): void
    {
        if (! $this->album || $photoIds === []) {
            return;
        }

        $deletingCover = Photo::whereIn('id', $photoIds)
            ->where('photo_album_id', $this->album->id)
            ->where('image_path', $this->album->cover_image_path)
            ->exists();

        Photo::whereIn('id', $photoIds)->where('photo_album_id', $this->album->id)->delete();

        if ($deletingCover) {
            $this->album->update(['cover_image_path' => $this->album->photos()->first()?->image_path]);
        }
    }

    /**
     * Tira uma foto da lista de uploads pendentes (ainda não salvos) antes
     * de clicar em "Salvar" — ex. o admin escolheu/arrastou fotos demais
     * e quer tirar uma antes de enviar.
     */
    public function removeNewPhoto(int $index): void
    {
        unset($this->newPhotos[$index]);
        $this->newPhotos = array_values($this->newPhotos);
    }

    public function save(): void
    {
        abort_unless(auth()->user()->can($this->album ? 'photos.update' : 'photos.create'), 403);

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
        ];

        $isNew = ! $this->album;

        if ($this->album) {
            $changes = ActivityLogger::diff($this->album, $data);
            $this->album->update($data);
            if ($changes !== []) {
                ActivityLogger::updated($this->album, $changes);
            }
            $album = $this->album;
        } else {
            // Álbum novo entra publicado — despublicar é uma ação separada
            // na tabela (togglePublish), não um campo deste formulário.
            $album = PhotoAlbum::create($data + ['is_published' => true]);
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
        $this->dispatch('toast', title: 'Álbum de Fotos', message: $isNew ? 'Álbum criado com sucesso.' : 'Álbum atualizado com sucesso.', variant: 'success');
        $this->reset(['title', 'description', 'eventDate', 'newPhotos']);
    }

    public function render()
    {
        return view('livewire.admin.photos.photo-album-form');
    }
}
