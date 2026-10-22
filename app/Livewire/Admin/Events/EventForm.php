<?php

namespace App\Livewire\Admin\Events;

use App\Models\Document;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\Media;
use App\Services\ActivityLogger;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Formulário admin de criação/edição de eventos da agenda (título, local,
 * imagem de capa e datas de início/fim). Imagem de capa e arquivo anexo
 * podem ser um upload novo ou um já existente na Biblioteca de Mídia
 * (evita duplicar) — mesmo padrão usado em Documentos.
 */
class EventForm extends Component
{
    use WithFileUploads;

    public ?Event $event = null;

    #[Validate('required|string|max:255')]
    public string $title = '';

    #[Validate('nullable|string|max:1000')]
    public string $description = '';

    #[Validate('nullable|string|max:255')]
    public string $location = '';

    #[Validate('nullable|url|max:2048')]
    public string $link = '';

    #[Validate('required|date')]
    public string $startsAt = '';

    #[Validate('nullable|date|after_or_equal:startsAt')]
    public string $endsAt = '';

    #[Validate('required|exists:categories,id,type,event')]
    public ?int $categoryId = null;

    public $coverImage = null;

    public ?string $existingCoverImage = null;

    public bool $useExistingCoverImage = false;

    public string $existingCoverImageSearch = '';

    public $file = null;

    public ?string $existingFilePath = null;

    public bool $useExistingFile = false;

    public string $existingFileSearch = '';

    public function mount(?Event $event = null, ?string $prefillDate = null): void
    {
        if ($event) {
            $this->event = $event;
            $this->title = $event->title;
            $this->description = (string) $event->description;
            $this->location = (string) $event->location;
            $this->link = (string) $event->link;
            $this->startsAt = $event->starts_at->format('Y-m-d\TH:i');
            $this->endsAt = $event->ends_at?->format('Y-m-d\TH:i') ?? '';
            $this->categoryId = $event->event_category_id;
            $this->existingCoverImage = $event->cover_image;
            $this->existingFilePath = $event->file_path;
        } elseif ($prefillDate) {
            $this->startsAt = $prefillDate.'T09:00';
        }
    }

    public function pickExistingCoverImage(string $path): void
    {
        $this->existingCoverImage = $path;
        $this->coverImage = null;
    }

    public function removeCoverImage(): void
    {
        $this->existingCoverImage = null;
        $this->coverImage = null;
    }

    public function pickExistingFile(string $path): void
    {
        $this->existingFilePath = $path;
        $this->file = null;
    }

    public function removeFile(): void
    {
        $this->existingFilePath = null;
        $this->file = null;
    }

    public function save(): void
    {
        abort_unless(auth()->user()->can($this->event ? 'events.update' : 'events.create'), 403);

        $this->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'location' => 'nullable|string|max:255',
            'link' => 'nullable|url|max:2048',
            'startsAt' => 'required|date',
            'endsAt' => 'nullable|date|after_or_equal:startsAt',
            'categoryId' => 'required|exists:categories,id,type,event',
            'coverImage' => 'nullable|image|mimes:jpg,jpeg,png,webp,gif|max:20480',
            'file' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,csv,ppt,pptx,zip,rar,7z,mp4,mov,avi,webm,jpg,jpeg,png,webp,gif|max:51200',
        ]);

        // As duas listas de "arquivo já existente" (capa/anexo) são
        // montadas a partir de Event/Document/Media reais — reconfere aqui
        // que o caminho escolhido via pickExistingCoverImage/pickExistingFile
        // ainda está num desses dois conjuntos (ou é a própria capa/anexo já
        // salvos neste evento, que o formulário carrega em mount() sem
        // passar por pickExisting*), em vez de confiar que o caminho
        // recebido daquelas chamadas é necessariamente um dos que a própria
        // tela ofereceu.
        if ($this->existingCoverImage && ! $this->coverImage && $this->existingCoverImage !== $this->event?->cover_image) {
            abort_unless($this->existingCoverImageOptions->pluck('path')->contains($this->existingCoverImage), 403);
        }

        if ($this->existingFilePath && ! $this->file && $this->existingFilePath !== $this->event?->file_path) {
            abort_unless($this->existingFileOptions->pluck('path')->contains($this->existingFilePath), 403);
        }

        $data = [
            'title' => $this->title,
            'description' => $this->description !== '' ? $this->description : null,
            'location' => $this->location !== '' ? $this->location : null,
            'link' => $this->link !== '' ? $this->link : null,
            'starts_at' => $this->startsAt,
            'ends_at' => $this->endsAt !== '' ? $this->endsAt : null,
            'event_category_id' => $this->categoryId,
            'cover_image' => $this->coverImage
                ? $this->coverImage->storeAs('events/'.Str::random(12), $this->coverImage->getClientOriginalName(), 'public')
                : $this->existingCoverImage,
            'file_path' => $this->file
                ? $this->file->storeAs('events/'.Str::random(12), $this->file->getClientOriginalName(), 'public')
                : $this->existingFilePath,
        ];

        if ($this->event) {
            $changes = ActivityLogger::diff($this->event, $data);
            $this->event->update($data);
            if ($changes !== []) {
                ActivityLogger::updated($this->event, $changes);
            }
            $this->dispatch('toast', title: 'Evento', message: 'Evento atualizado com sucesso.', variant: 'success');
        } else {
            $data['order'] = (Event::max('order') ?? 0) + 1;
            $event = Event::create($data);
            ActivityLogger::created($event);
            $this->dispatch('toast', title: 'Evento', message: 'Evento criado com sucesso.', variant: 'success');
        }

        $this->dispatch('event-saved');
        $this->reset([
            'title', 'description', 'location', 'link', 'startsAt', 'endsAt', 'categoryId',
            'coverImage', 'existingCoverImage', 'useExistingCoverImage', 'existingCoverImageSearch',
            'file', 'existingFilePath', 'useExistingFile', 'existingFileSearch',
        ]);
    }

    /**
     * Mídia solta (uploads direto na Biblioteca de Mídia) — memoizada
     * porque tanto existingCoverImageOptions() quanto existingFileOptions()
     * precisam dela, e sem isso `Media::all()` rodava duas vezes a cada
     * render (um a mais do que o necessário, já pouco ideal por rodar em
     * toda digitação no formulário).
     */
    #[Computed]
    public function allMedia(): Collection
    {
        return Media::all();
    }

    /**
     * Imagens já existentes (capas de outros eventos + uploads de imagem
     * direto na Biblioteca de Mídia), pra reaproveitar sem precisar enviar
     * de novo.
     *
     * @return Collection<int, array{path: string, name: string, source: string}>
     */
    #[Computed]
    public function existingCoverImageOptions(): Collection
    {
        $options = collect();

        Event::whereNotNull('cover_image')
            ->when($this->event, fn ($q) => $q->where('id', '!=', $this->event->id))
            ->get()
            ->each(fn (Event $e) => $options->push(['path' => $e->cover_image, 'name' => $e->title, 'source' => 'Eventos']));

        $this->allMedia->each(function (Media $m) use ($options) {
            $extension = strtolower(pathinfo($m->path, PATHINFO_EXTENSION));
            if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'], true)) {
                $options->push(['path' => $m->path, 'name' => $m->name, 'source' => $m->source ?? 'Outros']);
            }
        });

        $options = $options->filter(fn (array $o) => Storage::disk('public')->exists($o['path']));

        if ($this->existingCoverImageSearch !== '') {
            $options = $options->filter(fn (array $o) => str_contains(strtolower($o['name']), strtolower($this->existingCoverImageSearch)));
        }

        return $options->values();
    }

    /**
     * Arquivos já existentes (documentos cadastrados + anexos de outros
     * eventos + uploads diretos da Biblioteca de Mídia que não sejam
     * imagem), pra reaproveitar sem precisar enviar de novo.
     *
     * @return Collection<int, array{path: string, name: string, source: string}>
     */
    #[Computed]
    public function existingFileOptions(): Collection
    {
        $options = collect();

        Document::whereNotNull('file_path')->get()
            ->each(fn (Document $d) => $options->push(['path' => $d->file_path, 'name' => $d->title, 'source' => 'Documentos']));

        Event::whereNotNull('file_path')
            ->when($this->event, fn ($q) => $q->where('id', '!=', $this->event->id))
            ->get()
            ->each(fn (Event $e) => $options->push(['path' => $e->file_path, 'name' => $e->title, 'source' => 'Eventos']));

        $this->allMedia->each(function (Media $m) use ($options) {
            $extension = strtolower(pathinfo($m->path, PATHINFO_EXTENSION));
            if (! in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'], true)) {
                $options->push(['path' => $m->path, 'name' => $m->name, 'source' => $m->source ?? 'Outros']);
            }
        });

        $options = $options->filter(fn (array $o) => Storage::disk('public')->exists($o['path']));

        if ($this->existingFileSearch !== '') {
            $options = $options->filter(fn (array $o) => str_contains(strtolower($o['name']), strtolower($this->existingFileSearch)));
        }

        return $options->values();
    }

    public function render()
    {
        return view('livewire.admin.events.event-form', [
            'categories' => EventCategory::orderBy('order')->get(),
            'existingCoverImageOptions' => $this->existingCoverImageOptions,
            'existingFileOptions' => $this->existingFileOptions,
        ]);
    }
}
