<?php

namespace App\Livewire\Admin;

use App\Models\Document;
use App\Models\Media;
use App\Models\Photo;
use App\Services\ActivityLogger;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

/**
 * Biblioteca de Mídia: reúne num só lugar todo arquivo real já enviado por
 * upload no sistema (documentos, fotos de álbum), mais os arquivos
 * enviados direto aqui (guardados em `Media`). Mural é posts/conteúdo
 * editorial, não arquivo de mídia — por isso não entra aqui. O upload
 * direto exige escolher antes uma origem nas abas de filtro (Documentos ou
 * Fotos) — é essa origem que define a categoria do arquivo, sem
 * "biblioteca" solta sem dono. Renomear/excluir aqui agem sobre o
 * conteúdo real dono do arquivo (Photo, Document ou Media) — cada
 * "ownerType" decide o que é seguro fazer. Vídeos não entram porque hoje
 * são só links de embed (YouTube/Vimeo), não upload.
 */
class MediaLibraryIndex extends Component
{
    use WithFileUploads, WithPagination;

    private const PER_PAGE = 9;

    public string $search = '';

    public string $source = '';

    public string $view = 'list';

    /** @var array<int, TemporaryUploadedFile> */
    public array $newMediaFiles = [];

    public ?int $renamingMediaId = null;

    public ?string $renamingOwnerType = null;

    public string $renamingName = '';

    /** @var array<int, string> */
    public array $selected = [];

    /**
     * Liga durante bulkDelete() pra cada exclusão individual não disparar
     * seu próprio toast — só o resumo no final.
     */
    private bool $suppressToast = false;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedSource(): void
    {
        $this->resetPage();
        $this->selected = [];
    }

    /**
     * Chave única de seleção de um item (não tem "id" próprio — é
     * montado a partir do dono real do arquivo): "ownerType:ownerId:fieldIndex".
     */
    public static function selectionKey(array $item): string
    {
        return "{$item['ownerType']}:{$item['ownerId']}:{$item['fieldIndex']}";
    }

    public function toggleSelectAllOnPage(): void
    {
        $pageKeys = $this->allMedia()
            ->when($this->source !== '', fn ($items) => $items->where('source', $this->source))
            ->when($this->search !== '', fn ($items) => $items->filter(fn (array $item) => str_contains(strtolower($item['name']), strtolower($this->search))))
            ->filter(fn (array $item) => $item['canDelete'])
            ->forPage($this->getPage(), self::PER_PAGE)
            ->map(fn (array $item) => self::selectionKey($item))
            ->values()
            ->all();

        if (array_diff($pageKeys, $this->selected) === []) {
            $this->selected = array_values(array_diff($this->selected, $pageKeys));
        } else {
            $this->selected = array_values(array_unique(array_merge($this->selected, $pageKeys)));
        }
    }

    public function deselectAll(): void
    {
        $this->selected = [];
    }

    public function bulkDelete(): void
    {
        $count = count($this->selected);

        $this->suppressToast = true;

        foreach ($this->selected as $key) {
            [$ownerType, $ownerId, $fieldIndex] = array_pad(explode(':', $key), 3, '');
            $this->deleteItem($ownerType, (int) $ownerId, $fieldIndex !== '' ? (int) $fieldIndex : null);
        }

        $this->suppressToast = false;
        $this->selected = [];
        $this->dispatch('toast', title: 'Mídia', message: "{$count} arquivo(s) excluído(s) com sucesso.", variant: 'danger');
    }

    /**
     * Dispara ao soltar/escolher um ou vários arquivos de uma vez no botão
     * "Enviar arquivo" — cada arquivo gera seu próprio registro `Media`.
     */
    public function updatedNewMediaFiles(): void
    {
        $this->validate(['newMediaFiles.*' => 'file|max:10240']);

        // Regra: só pode enviar depois de escolher uma origem nas abas de
        // filtro (Documentos ou Fotos) — é essa origem que define onde o
        // arquivo aparece/pertence, em vez de cair numa "biblioteca" solta
        // sem categoria.
        if ($this->source === '') {
            $this->newMediaFiles = [];
            $this->addError('newMediaFiles', 'Selecione uma origem (Documentos ou Fotos) antes de enviar um arquivo.');

            return;
        }

        $count = 0;

        foreach ($this->newMediaFiles as $file) {
            $path = $file->store('media', 'public');

            Media::create([
                'name' => $file->getClientOriginalName(),
                'path' => $path,
                'source' => $this->source,
                'user_id' => auth()->id(),
            ]);
            $count++;
        }

        $this->newMediaFiles = [];
        $this->dispatch('toast', title: 'Mídia', message: $count > 1 ? "{$count} arquivos enviados com sucesso." : 'Arquivo enviado com sucesso.', variant: 'success');
    }

    public function deleteMedia(int $mediaId): void
    {
        $media = Media::findOrFail($mediaId);
        Storage::disk('public')->delete($media->path);
        $media->delete();
        $this->maybeDispatchDeleteToast('Arquivo excluído com sucesso.');
    }

    public function openRenameModal(string $ownerType, int $ownerId, string $currentName): void
    {
        $this->renamingOwnerType = $ownerType;
        $this->renamingMediaId = $ownerId;
        $this->renamingName = $currentName;
        $this->dispatch('open-modal', 'media-rename');
    }

    public function renameItem(): void
    {
        $this->validate(['renamingName' => 'required|string|max:255']);

        match ($this->renamingOwnerType) {
            'media' => Media::findOrFail($this->renamingMediaId)->update(['name' => $this->renamingName]),
            'photo' => $this->renamePhoto(),
            'document' => $this->renameDocument(),
            default => null,
        };

        $this->dispatch('media-item-renamed', ownerType: $this->renamingOwnerType, ownerId: $this->renamingMediaId, name: $this->renamingName);

        $this->renamingMediaId = null;
        $this->renamingOwnerType = null;
        $this->dispatch('close-modal', 'media-rename');
        $this->dispatch('toast', title: 'Mídia', message: 'Arquivo renomeado com sucesso.', variant: 'success');
    }

    private function renamePhoto(): void
    {
        abort_unless(auth()->user()->can('photos.update'), 403);
        Photo::findOrFail($this->renamingMediaId)->update(['caption' => $this->renamingName]);
    }

    private function renameDocument(): void
    {
        abort_unless(auth()->user()->can('documents.update'), 403);
        Document::findOrFail($this->renamingMediaId)->update(['title' => $this->renamingName]);
    }

    public function deletePhoto(int $photoId): void
    {
        abort_unless(auth()->user()->can('photos.delete'), 403);

        $photo = Photo::findOrFail($photoId);
        $album = $photo->album;
        $wasCover = $album && $album->cover_image_path === $photo->image_path;

        Storage::disk('public')->delete($photo->image_path);
        $photo->delete();

        if ($wasCover) {
            $album->update(['cover_image_path' => $album->photos()->first()?->image_path]);
        }

        $this->maybeDispatchDeleteToast('Foto excluída com sucesso.');
    }

    public function deleteDocument(int $documentId): void
    {
        abort_unless(auth()->user()->can('documents.delete'), 403);

        $document = Document::findOrFail($documentId);
        Storage::disk('public')->delete($document->file_path);
        $document->delete();
        ActivityLogger::deleted($document);

        $this->maybeDispatchDeleteToast('Documento excluído com sucesso.');
    }

    private function maybeDispatchDeleteToast(string $message): void
    {
        if (! $this->suppressToast) {
            $this->dispatch('toast', title: 'Mídia', message: $message, variant: 'danger');
        }
    }

    /**
     * Ponto único de exclusão chamado pela view (dropdown da tabela, botão
     * da grade e painel de prévia): decide qual exclusão real rodar a
     * partir do "ownerType" do item, sem a view precisar saber o nome de
     * cada método específico.
     */
    public function deleteItem(string $ownerType, int $ownerId, ?int $fieldIndex = null): void
    {
        match ($ownerType) {
            'media' => $this->deleteMedia($ownerId),
            'photo' => $this->deletePhoto($ownerId),
            'document' => $this->deleteDocument($ownerId),
            default => null,
        };
    }

    /**
     * @return Collection<int, array{name: string, url: string, path: string, size: int, source: string, type: string, date: Carbon, mediaId: ?int, ownerType: ?string, ownerId: ?int, fieldIndex: ?int, canRename: bool, canDelete: bool}>
     */
    private function allMedia(): Collection
    {
        $items = collect();

        foreach (Document::all() as $document) {
            $items->push($this->item($document->title, $document->file_path, 'Documentos', $document->created_at, ownerType: 'document', ownerId: $document->id));
        }

        foreach (Photo::with('album')->get() as $photo) {
            $items->push($this->item($photo->caption ?: $photo->album?->title ?? 'Foto', $photo->image_path, 'Fotos', $photo->created_at, ownerType: 'photo', ownerId: $photo->id));
        }

        foreach (Media::all() as $media) {
            $items->push($this->item($media->name, $media->path, $media->source ?? 'Outros', $media->created_at, mediaId: $media->id, ownerType: 'media', ownerId: $media->id));
        }

        return $items->filter(fn (?array $item) => $item !== null)->sortByDesc('date')->values();
    }

    private function item(string $name, string $path, string $source, $date, ?int $mediaId = null, ?string $ownerType = null, ?int $ownerId = null, ?int $fieldIndex = null): ?array
    {
        if (! Storage::disk('public')->exists($path)) {
            return null;
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $isImage = in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'], true);
        $style = $this->iconStyleFor($extension);

        return [
            'name' => $name,
            'url' => Storage::url($path),
            'path' => $path,
            'size' => Storage::disk('public')->size($path),
            'source' => $source,
            'type' => $isImage ? 'image' : 'document',
            'extension' => $extension,
            'date' => $date,
            'mediaId' => $mediaId,
            'ownerType' => $ownerType,
            'ownerId' => $ownerId,
            'fieldIndex' => $fieldIndex,
            'canRename' => in_array($ownerType, ['media', 'photo', 'document'], true),
            'canDelete' => in_array($ownerType, ['media', 'photo', 'document'], true),
            'icon' => $style['icon'],
            'gradient' => $style['gradient'],
        ];
    }

    /**
     * Ícone e gradiente de fundo por extensão, pra documentos (imagens
     * mostram a miniatura de verdade, não precisam disso).
     *
     * @return array{icon: string, gradient: string}
     */
    private function iconStyleFor(string $extension): array
    {
        return match ($extension) {
            'pdf' => ['icon' => 'file-pdf', 'gradient' => 'from-rose-500/25 to-rose-500/10'],
            'doc', 'docx' => ['icon' => 'file-word', 'gradient' => 'from-blue-500/25 to-blue-500/10'],
            'ppt', 'pptx' => ['icon' => 'file-powerpoint', 'gradient' => 'from-amber-500/25 to-amber-500/10'],
            'xls', 'xlsx', 'csv' => ['icon' => 'file-excel', 'gradient' => 'from-emerald-500/25 to-emerald-500/10'],
            'mp4', 'mov', 'webm' => ['icon' => 'file-video', 'gradient' => 'from-purple-500/25 to-purple-500/10'],
            'mp3', 'wav', 'ogg' => ['icon' => 'file-audio', 'gradient' => 'from-teal-500/25 to-teal-500/10'],
            default => ['icon' => 'file-lines', 'gradient' => 'from-slate-500/25 to-slate-500/10'],
        };
    }

    public static function sourceIcon(string $source): string
    {
        return match ($source) {
            'Documentos' => 'file-lines',
            'Fotos' => 'images',
            default => 'circle-question',
        };
    }

    public static function sourceColor(string $source): string
    {
        return match ($source) {
            'Documentos' => '#D97706',
            'Fotos' => '#DB2777',
            default => '#64748B',
        };
    }

    /**
     * Monta a chamada JS (`$wire.deleteItem(...)`) de exclusão de um item,
     * já no formato esperado por `deleteItem()` — a view não precisa saber
     * o nome do método real de cada origem.
     *
     * @param  array{ownerType: ?string, ownerId: ?int, fieldIndex: ?int}  $item
     */
    public static function deleteAction(array $item): string
    {
        $fieldIndex = $item['fieldIndex'] !== null ? $item['fieldIndex'] : 'null';

        return "deleteItem('{$item['ownerType']}', {$item['ownerId']}, {$fieldIndex})";
    }

    public static function formatSize(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 1).' MB';
        }

        return number_format($bytes / 1024, 0).' KB';
    }

    public function render()
    {
        $allItems = $this->allMedia();
        $media = $allItems;

        if ($this->source !== '') {
            $media = $media->where('source', $this->source);
        }

        if ($this->search !== '') {
            $media = $media->filter(fn (array $item) => str_contains(strtolower($item['name']), strtolower($this->search)));
        }

        $media = $media->values();
        $page = $this->getPage();
        $paginatedMedia = new LengthAwarePaginator(
            $media->forPage($page, self::PER_PAGE)->values(),
            $media->count(),
            self::PER_PAGE,
            $page,
            ['path' => request()->url(), 'query' => request()->query()],
        );

        $sources = ['Documentos', 'Fotos'];

        return view('livewire.admin.media-library-index', [
            'media' => $paginatedMedia,
            'sources' => $sources,
            'sourceStats' => collect($sources)->map(fn (string $sourceOption) => [
                'source' => $sourceOption,
                'count' => $allItems->where('source', $sourceOption)->count(),
                'size' => $allItems->where('source', $sourceOption)->sum('size'),
            ]),
            'totalCount' => $allItems->count(),
            'imageCount' => $allItems->where('type', 'image')->count(),
            'documentCount' => $allItems->where('type', 'document')->count(),
            'imageSize' => $allItems->where('type', 'image')->sum('size'),
            'documentSize' => $allItems->where('type', 'document')->sum('size'),
            'totalSize' => $allItems->sum('size'),
        ])->layout('layouts.admin', ['title' => 'Biblioteca de Mídia']);
    }
}
