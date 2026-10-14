<?php

namespace App\Livewire\Admin\Articles;

use App\Models\Article;
use App\Models\Category;
use App\Services\ActivityLogger;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Página admin de criação/edição de artigos do Mural (notícias/avisos
 * publicados aos usuários): conteúdo, categorias (um artigo pode ter
 * várias), capa (cor/ícone ou imagem real), autor, campos personalizados
 * e status de publicação. "Salvar" grava e volta pra listagem; "Aplicar"
 * grava sem sair da página, só pra conferir o resultado.
 */
class ArticleForm extends Component
{
    use WithFileUploads;

    public ?Article $article = null;

    #[Validate('required|string|max:255')]
    public string $title = '';

    #[Validate('required|string|max:500')]
    public string $excerpt = '';

    #[Validate('required|string')]
    public string $body = '';

    /** @var array<int, int> */
    public array $categoryIds = [];

    public string $newCategoryId = '';

    #[Validate('required|string|max:20')]
    public string $coverColor = '#064e3b';

    #[Validate('required|regex:/^[a-z0-9-]+$/')]
    public string $coverIcon = 'newspaper';

    public string $coverLabel = '';

    #[Validate('nullable|image|max:4096')]
    public $featuredImage = null;

    public ?string $existingFeaturedImage = null;

    public int $authorId = 0;

    public string $scheduledDate = '';

    public string $scheduledTime = '';

    /** 'now' publica com a data/hora atual; 'schedule' usa a data/hora escolhida acima. */
    public string $publishMode = 'now';

    /** @var array<int, array{type: string, label: string, value: string, file: mixed, existingPath: ?string}> */
    public array $customFields = [];

    #[Validate('nullable|image|max:4096')]
    public $bodyImage = null;

    public function mount(?Article $article = null): void
    {
        $this->authorId = auth()->id();
        $scheduledAt = now();

        if ($article) {
            $this->article = $article;
            $this->title = $article->title;
            $this->excerpt = $article->excerpt;
            $this->body = $article->body;
            $this->categoryIds = $article->categories->pluck('id')->all();
            $this->coverColor = $article->cover_color;
            $this->coverIcon = $article->cover_icon;
            $this->coverLabel = $article->cover_label ?? '';
            $this->existingFeaturedImage = $article->featured_image;
            $this->authorId = $article->author_id;
            $this->customFields = collect($article->custom_fields ?? [])
                ->map(fn (array $field) => [
                    'type' => $field['type'],
                    'label' => $field['label'],
                    'value' => $field['value'] ?? '',
                    'file' => null,
                    'existingPath' => in_array($field['type'], ['arquivo', 'imagem'], true) ? ($field['value'] ?? null) : null,
                ])
                ->all();
            // Rascunho sem data de publicação ainda: usa a data em que o
            // artigo foi criado (não "agora"), pra não quebrar a ordem de
            // criação quando o admin publicar sem escolher uma data nova.
            $scheduledAt = $article->published_at ?? $article->created_at;
            $this->publishMode = $article->isScheduled() ? 'schedule' : 'now';
        }

        $this->scheduledDate = $scheduledAt->format('Y-m-d');
        $this->scheduledTime = $scheduledAt->format('H:i');
    }

    /**
     * Combina data + hora escolhidas no card "Publicado" num único Carbon,
     * usado como `published_at` ao publicar/agendar ou reagendar o artigo.
     */
    private function scheduledAt(): Carbon
    {
        return Carbon::parse("{$this->scheduledDate} {$this->scheduledTime}");
    }

    /**
     * Garante que uma data/hora de agendamento futura foi escolhida quando
     * o admin optou por "Agendar" — sem isso o campo existiria só de nome,
     * já que uma data passada equivale a publicar agora mesmo.
     */
    private function validateScheduleIsFuture(): void
    {
        if ($this->publishMode === 'schedule' && ! $this->scheduledAt()->isFuture()) {
            throw ValidationException::withMessages([
                'scheduledDate' => 'Escolha uma data e hora futuras para agendar, ou selecione "Postar agora".',
            ]);
        }
    }

    /**
     * Ao trocar pra "Postar agora", adianta os campos de data/hora pro
     * momento atual — sem isso o admin podia deixar uma data antiga/futura
     * esquecida nos campos (escondidos na UI nesse modo) e publicar com ela
     * em vez de "agora" de verdade.
     */
    public function updatedPublishMode(): void
    {
        if ($this->publishMode === 'now') {
            $this->scheduledDate = now()->format('Y-m-d');
            $this->scheduledTime = now()->format('H:i');
        }
    }

    public function addCategory(): void
    {
        $id = (int) $this->newCategoryId;

        if ($id && ! in_array($id, $this->categoryIds, true)) {
            $this->categoryIds[] = $id;
        }

        $this->newCategoryId = '';
    }

    public function removeCategory(int $categoryId): void
    {
        $this->categoryIds = array_values(array_diff($this->categoryIds, [$categoryId]));
    }

    public function removeFeaturedImage(): void
    {
        $this->existingFeaturedImage = null;
        $this->featuredImage = null;
    }

    /**
     * Chamado automaticamente pelo Livewire quando o input de imagem do
     * corpo do artigo recebe um arquivo. Guarda no disco (em vez de mandar
     * a imagem em base64 pro editor, que inflava o payload do Livewire
     * acima do limite) e avisa o editor Tiptap via evento de navegador com
     * a URL já pronta pra inserir no texto.
     */
    public function updatedBodyImage(): void
    {
        $this->validate(['bodyImage' => 'nullable|image|max:4096']);

        $path = $this->bodyImage->store('articles/body', 'public');
        $this->dispatch('tiptap-image-inserted', url: Storage::url($path));
        $this->bodyImage = null;
    }

    public function addCustomField(): void
    {
        $this->customFields[] = [
            'type' => 'texto',
            'label' => '',
            'value' => '',
            'file' => null,
            'existingPath' => null,
        ];
    }

    public function removeCustomField(int $index): void
    {
        unset($this->customFields[$index]);
        $this->customFields = array_values($this->customFields);
    }

    public function togglePublish(): void
    {
        abort_unless($this->article && auth()->user()->can('articles.update'), 403);

        if ($this->article->is_published) {
            $this->article->update(['is_published' => false]);
            ActivityLogger::deactivated($this->article);
            $this->dispatch('toast', title: 'Artigo', message: 'Artigo despublicado.', variant: 'warning');

            return;
        }

        $this->validate([
            'scheduledDate' => 'required|date',
            'scheduledTime' => 'required|date_format:H:i',
        ]);
        $this->validateScheduleIsFuture();

        $publishedAt = $this->scheduledAt();

        $this->article->update([
            'is_published' => true,
            'published_at' => $publishedAt,
        ]);
        ActivityLogger::activated($this->article);

        $message = $publishedAt->isFuture() ? 'Artigo agendado com sucesso.' : 'Artigo publicado com sucesso.';
        $this->dispatch('toast', title: 'Artigo', message: $message, variant: 'success');
    }

    /**
     * Monta os dados validados, salva o artigo (criando ou atualizando) e
     * sincroniza os campos personalizados. Usado por save() e apply().
     */
    protected function persistArticle(): Article
    {
        $this->validate([
            'title' => 'required|string|max:255',
            'excerpt' => 'required|string|max:500',
            'body' => 'required|string',
            'categoryIds.*' => 'exists:categories,id',
            'coverColor' => 'required|string|max:20',
            'coverIcon' => 'required|regex:/^[a-z0-9-]+$/',
            'featuredImage' => 'nullable|image|max:4096',
            'authorId' => 'required|exists:users,id',
            'scheduledDate' => 'required|date',
            'scheduledTime' => 'required|date_format:H:i',
            'customFields.*.type' => 'required|in:texto,link,arquivo,imagem',
            'customFields.*.label' => 'required|string|max:255',
            'customFields.*.file' => 'nullable|file|max:10240',
        ]);

        $data = [
            'title' => $this->title,
            'excerpt' => $this->excerpt,
            'body' => $this->body,
            'cover_color' => $this->coverColor,
            'cover_icon' => $this->coverIcon,
            'cover_label' => $this->coverLabel !== '' ? $this->coverLabel : null,
            'author_id' => $this->authorId,
            'custom_fields' => $this->resolveCustomFields(),
        ];

        if ($this->article?->is_published) {
            $this->validateScheduleIsFuture();
            $data['published_at'] = $this->scheduledAt();
        }

        if ($this->featuredImage) {
            $data['featured_image'] = $this->featuredImage->store('articles', 'public');
        } elseif ($this->existingFeaturedImage === null) {
            $data['featured_image'] = null;
        }

        if ($this->article) {
            $changes = ActivityLogger::diff($this->article, $data);
            $this->article->update($data);
            if ($changes !== []) {
                ActivityLogger::updated($this->article, $changes);
            }
            $article = $this->article;
        } else {
            // Artigo novo entra publicado (ou agendado) de acordo com o que
            // já estava escolhido em "Quando publicar" — não faz sentido
            // forçar rascunho e exigir um passo extra de publicar depois.
            $this->validateScheduleIsFuture();
            $article = Article::create($data + [
                'is_published' => true,
                'published_at' => $this->scheduledAt(),
            ]);
            ActivityLogger::created($article);
            $this->article = $article;
        }

        $article->categories()->sync($this->categoryIds);

        return $article;
    }

    /**
     * Para "arquivo"/"imagem", envia o upload (se houver um novo) e guarda
     * só o caminho no JSON — o objeto de upload em si não é persistível.
     */
    private function resolveCustomFields(): array
    {
        return array_map(function (array $field) {
            $value = $field['value'] ?? '';

            if (in_array($field['type'], ['arquivo', 'imagem'], true)) {
                $value = $field['file']
                    ? $field['file']->store('articles/custom', 'public')
                    : ($field['existingPath'] ?? '');
            }

            return [
                'type' => $field['type'],
                'label' => $field['label'],
                'value' => $value,
            ];
        }, $this->customFields);
    }

    public function apply(): void
    {
        $article = $this->persistArticle();

        $this->existingFeaturedImage = $article->featured_image;
        $this->featuredImage = null;

        foreach ($this->customFields as $index => $field) {
            if (in_array($field['type'], ['arquivo', 'imagem'], true)) {
                $this->customFields[$index]['existingPath'] = $article->custom_fields[$index]['value'] ?? null;
                $this->customFields[$index]['file'] = null;
            }
        }

        $this->dispatch('toast', title: 'Artigo', message: 'Alterações aplicadas. Confira abaixo se está correto.', variant: 'success');
    }

    public function save(): void
    {
        $this->persistArticle();

        session()->flash('toast', ['title' => 'Artigo', 'message' => 'Artigo salvo com sucesso.', 'variant' => 'success']);
        $this->redirect(route('admin.articles.index'), navigate: true);
    }

    public function render()
    {
        $categories = Category::orderBy('name')->get();

        return view('livewire.admin.articles.article-form', [
            'selectedCategories' => $categories->whereIn('id', $this->categoryIds),
            'availableCategories' => $categories->whereNotIn('id', $this->categoryIds),
        ])->layout('layouts.admin', ['title' => $this->article ? 'Editar Artigo' : 'Novo Artigo']);
    }
}
