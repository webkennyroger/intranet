<?php

namespace App\Livewire\Admin;

use App\Models\Article;
use App\Models\BirthdayBanner;
use App\Models\Document;
use App\Models\Event;
use App\Models\Media;
use App\Models\Photo;
use App\Services\ActivityLogger;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

/**
 * Biblioteca de Mídia: reúne num só lugar todo arquivo real já enviado por
 * upload no sistema, organizado em uma pasta por seção de conteúdo (Mural,
 * Notícia, SOS DPEMT, Fotos, Documentos, Eventos, Aniversariantes) — cada
 * arquivo aparece na pasta da seção que o usa, nunca numa "biblioteca"
 * solta sem dono. Os uploads feitos direto aqui (guardados em `Media`)
 * continuam exigindo escolher antes uma origem nas abas de filtro
 * (Documentos ou Fotos), já que não têm um registro próprio de onde vieram.
 * Renomear/excluir aqui agem sobre o conteúdo real dono do arquivo (Article,
 * Photo, Document, Media, BirthdayBanner ou um campo específico de Event) —
 * cada "ownerType" decide o que é seguro fazer; evento e banner não entram
 * em "canRename" porque o nome exibido já é o título/nome do próprio
 * registro, não um rótulo do arquivo em si. Vídeos não entram porque hoje
 * são só links de embed (YouTube/Vimeo), não upload.
 */
class MediaLibraryIndex extends Component
{
    use WithFileUploads, WithPagination;

    private const PER_PAGE = 9;

    /**
     * Pastas-raiz (Fotos/Documentos, por tipo de arquivo) e as subpastas
     * reais que cada uma agrupa — aparecem sempre, mesmo vazias, pra deixar
     * claro de cara onde cada seção do site guarda imagem e onde guarda
     * documento. "Eventos" entra nas duas porque um evento pode ter capa
     * (imagem) e anexo (documento) ao mesmo tempo.
     */
    private const FOLDER_SOURCES = [
        'Fotos' => ['Mural', 'Notícia', 'SOS DPEMT', 'Galeria', 'Aniversariantes', 'Conselho Superior', 'Eventos'],
        'Documentos' => ['Documentos', 'Eventos'],
    ];

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
     * Todos os itens já com o filtro de busca aplicado, mas AINDA sem o
     * filtro de subpasta (`source`) — é o universo usado tanto pra montar
     * as contagens de cada cartão quanto, com o filtro de `source` aplicado
     * em cima, pra listar os arquivos de dentro de uma subpasta.
     */
    private function searchedItems(): Collection
    {
        $items = $this->allMedia;

        if ($this->search !== '') {
            $items = $items->filter(fn (array $item) => str_contains(strtolower($item['name']), strtolower($this->search)));
        }

        return $items->values();
    }

    /**
     * Contagem/tamanho de cada subpasta real de uma pasta-raiz (Fotos ou
     * Documentos), restrita ao tipo de arquivo daquela pasta-raiz — é o que
     * faz "Eventos" mostrar só a capa (imagem) do lado de Fotos e só o
     * anexo (documento) do lado de Documentos.
     *
     * @param  array<int, string>  $sourceNames
     * @return Collection<int, array{source: string, count: int, size: int}>
     */
    private function statsFor(Collection $items, string $type, array $sourceNames): Collection
    {
        $typed = $items->where('type', $type);

        return collect($sourceNames)->map(fn (string $source) => [
            'source' => $source,
            'count' => $typed->where('source', $source)->count(),
            'size' => $typed->where('source', $source)->sum('size'),
        ]);
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
        $pageKeys = $this->searchedItems()
            ->when($this->source !== '', fn ($items) => $items->where('source', $this->source))
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
        abort_unless(auth()->user()->can('media.create'), 403);

        $this->validate(['newMediaFiles.*' => 'file|max:10240']);

        // Regra: só pode enviar depois de escolher uma pasta — é ela que
        // define onde o arquivo aparece/pertence, em vez de cair numa
        // "biblioteca" solta sem categoria.
        if ($this->source === '') {
            $this->newMediaFiles = [];
            $this->addError('newMediaFiles', 'Selecione uma pasta antes de enviar um arquivo.');

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
        abort_unless(auth()->user()->can('media.delete'), 403);

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
            'media' => $this->renameMedia(),
            'photo' => $this->renamePhoto(),
            'document' => $this->renameDocument(),
            'article' => $this->renameArticle(),
            default => null,
        };

        $this->dispatch('media-item-renamed', ownerType: $this->renamingOwnerType, ownerId: $this->renamingMediaId, name: $this->renamingName);

        $this->renamingMediaId = null;
        $this->renamingOwnerType = null;
        $this->dispatch('close-modal', 'media-rename');
        $this->dispatch('toast', title: 'Mídia', message: 'Arquivo renomeado com sucesso.', variant: 'success');
    }

    private function renameMedia(): void
    {
        abort_unless(auth()->user()->can('media.update'), 403);
        Media::findOrFail($this->renamingMediaId)->update(['name' => $this->renamingName]);
    }

    private function renamePhoto(): void
    {
        abort_unless(auth()->user()->can('photos.update'), 403);
        Photo::findOrFail($this->renamingMediaId)->update(['caption' => $this->renamingName]);
    }

    private function renameArticle(): void
    {
        abort_unless(auth()->user()->can('articles.update'), 403);
        Article::findOrFail($this->renamingMediaId)->update(['title' => $this->renamingName]);
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

    /**
     * Remove só o arquivo (capa ou anexo) de um evento, sem excluir o
     * evento em si — por isso usa o gate de atualizar, não de excluir.
     */
    public function deleteEventFile(int $eventId, int $fieldIndex): void
    {
        abort_unless(auth()->user()->can('events.update'), 403);

        $event = Event::findOrFail($eventId);
        $column = $fieldIndex === 0 ? 'cover_image' : 'file_path';

        Storage::disk('public')->delete($event->{$column});
        $event->update([$column => null]);

        $this->maybeDispatchDeleteToast('Arquivo excluído com sucesso.');
    }

    /**
     * Remove só a imagem de capa de um artigo, sem excluir o artigo em si.
     */
    public function deleteArticleFile(int $articleId): void
    {
        abort_unless(auth()->user()->can('articles.update'), 403);

        $article = Article::findOrFail($articleId);
        Storage::disk('public')->delete($article->featured_image);
        $article->update(['featured_image' => null]);

        $this->maybeDispatchDeleteToast('Imagem excluída com sucesso.');
    }

    /**
     * Remove só a imagem de um banner de aniversariantes, sem excluir o
     * banner em si (o mês continua existindo pra receber uma nova imagem).
     */
    public function deleteBirthdayBannerFile(int $bannerId): void
    {
        abort_unless(auth()->user()->can('birthday-banners.update'), 403);

        $banner = BirthdayBanner::findOrFail($bannerId);
        Storage::disk('public')->delete($banner->image_path);
        $banner->update(['image_path' => null]);

        $this->maybeDispatchDeleteToast('Imagem excluída com sucesso.');
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
            'event' => $this->deleteEventFile($ownerId, $fieldIndex ?? 0),
            'article' => $this->deleteArticleFile($ownerId),
            'birthday-banner' => $this->deleteBirthdayBannerFile($ownerId),
            default => null,
        };
    }

    /**
     * Varre todos os módulos com arquivo real (documentos, fotos, mídia
     * solta, eventos, artigos, banners, conselho) + confere cada caminho
     * no disco — isso é relativamente caro (table scan + I/O por item), daí
     * o #[Computed]: sem ele, render() e searchedItems() repetiam a mesma
     * varredura duas vezes a cada atualização.
     *
     * @return Collection<int, array{name: string, url: string, path: string, size: int, source: string, type: string, date: Carbon, mediaId: ?int, ownerType: ?string, ownerId: ?int, fieldIndex: ?int, canRename: bool, canDelete: bool}>
     */
    #[Computed]
    public function allMedia(): Collection
    {
        $items = collect();

        foreach (Document::all() as $document) {
            $items->push($this->item($document->title, $document->file_path, 'Documentos', $document->created_at, ownerType: 'document', ownerId: $document->id));
        }

        foreach (Photo::with('album')->get() as $photo) {
            $items->push($this->item($photo->caption ?: $photo->album?->title ?? 'Foto', $photo->image_path, 'Galeria', $photo->created_at, ownerType: 'photo', ownerId: $photo->id));
        }

        foreach (Media::all() as $media) {
            $items->push($this->item($media->name, $media->path, $media->source ?? 'Outros', $media->created_at, mediaId: $media->id, ownerType: 'media', ownerId: $media->id));
        }

        foreach (Event::all() as $event) {
            if ($event->cover_image) {
                $items->push($this->item($event->title, $event->cover_image, 'Eventos', $event->created_at, ownerType: 'event', ownerId: $event->id, fieldIndex: 0));
            }
            if ($event->file_path) {
                $items->push($this->item($event->title, $event->file_path, 'Eventos', $event->created_at, ownerType: 'event', ownerId: $event->id, fieldIndex: 1));
            }
        }

        foreach (Article::with('categories')->get() as $article) {
            if (! $article->featured_image) {
                continue;
            }

            $folders = $article->categories->isNotEmpty() ? $article->categories->pluck('name') : collect(['Mural']);

            foreach ($folders as $folder) {
                $items->push($this->item($article->title, $article->featured_image, $folder, $article->created_at, ownerType: 'article', ownerId: $article->id));
            }
        }

        foreach (BirthdayBanner::all() as $banner) {
            if (! $banner->image_path) {
                continue;
            }

            $items->push($this->item(BirthdayBanner::monthNames()[$banner->month] ?? 'Banner', $banner->image_path, 'Aniversariantes', $banner->created_at, ownerType: 'birthday-banner', ownerId: $banner->id));
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
            'canRename' => in_array($ownerType, ['media', 'photo', 'document', 'article'], true),
            'canDelete' => in_array($ownerType, ['media', 'photo', 'document', 'event', 'article', 'birthday-banner'], true),
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
            'Fotos', 'Galeria' => 'images',
            'Eventos' => 'calendar-days',
            'Mural' => 'thumbtack',
            'Notícia' => 'newspaper',
            'SOS DPEMT' => 'circle-exclamation',
            'Aniversariantes' => 'cake-candles',
            'Conselho Superior' => 'user-tie',
            default => 'circle-question',
        };
    }

    public static function sourceColor(string $source): string
    {
        return match ($source) {
            'Documentos' => '#D97706',
            'Fotos', 'Galeria' => '#DB2777',
            'Eventos' => '#299CDB',
            'Mural' => '#0C4A34',
            'Notícia' => '#2563EB',
            'SOS DPEMT' => '#DC2626',
            'Aniversariantes' => '#7C3AED',
            'Conselho Superior' => '#059669',
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
        $allItems = $this->allMedia;
        $searched = $this->searchedItems();

        $media = $this->source !== '' ? $searched->where('source', $this->source) : $searched;
        $media = $media->values();

        $page = $this->getPage();
        $paginatedMedia = new LengthAwarePaginator(
            $media->forPage($page, self::PER_PAGE)->values(),
            $media->count(),
            self::PER_PAGE,
            $page,
            ['path' => request()->url(), 'query' => request()->query()],
        );

        // Todas as subpastas reais num flat list — base do gráfico de rosca
        // (que não separa por pasta-raiz, só soma tudo por subpasta).
        $sources = ['Mural', 'Notícia', 'SOS DPEMT', 'Galeria', 'Documentos', 'Eventos', 'Aniversariantes', 'Conselho Superior'];
        $sourceStats = collect($sources)->map(fn (string $source) => [
            'source' => $source,
            'count' => $searched->where('source', $source)->count(),
            'size' => $searched->where('source', $source)->sum('size'),
        ]);

        return view('livewire.admin.media-library-index', [
            'media' => $paginatedMedia,
            'sources' => $sources,
            'sourceStats' => $sourceStats,
            // Os dois grupos visíveis de cartão (Fotos/Documentos), cada um
            // já com suas subpastas reais sempre à mostra — nada escondido
            // atrás de um clique.
            'photoFolders' => $this->statsFor($searched, 'image', self::FOLDER_SOURCES['Fotos']),
            'documentFolders' => $this->statsFor($searched, 'document', self::FOLDER_SOURCES['Documentos']),
            'totalCount' => $allItems->count(),
            'imageCount' => $allItems->where('type', 'image')->count(),
            'documentCount' => $allItems->where('type', 'document')->count(),
            'imageSize' => $allItems->where('type', 'image')->sum('size'),
            'documentSize' => $allItems->where('type', 'document')->sum('size'),
            'totalSize' => $allItems->sum('size'),
        ])->layout('layouts.admin', ['title' => 'Biblioteca de Mídia']);
    }
}
