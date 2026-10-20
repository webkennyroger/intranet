<?php

namespace App\Livewire\Site\Forum;

use App\Models\ForumTopic;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Página inicial do fórum: lista os tópicos criados pelos usuários,
 * paginados, e permite abrir um novo tópico com título e corpo.
 */
class ForumIndex extends Component
{
    use WithPagination;

    #[Validate('required|string|max:255')]
    public string $title = '';

    #[Validate('required|string|max:5000')]
    public string $body = '';

    public function createTopic(): void
    {
        $this->validate();

        $topic = ForumTopic::create([
            'author_id' => auth()->id(),
            'title' => $this->title,
            'body' => $this->body,
        ]);

        $this->reset(['title', 'body']);
        $this->dispatch('close-modal', 'new-topic');

        session()->flash('toast', ['title' => 'Fórum', 'message' => 'Tópico criado com sucesso.', 'variant' => 'success']);
        $this->redirect(route('forum.show', $topic));
    }

    /**
     * Isolada num island (ver forum-index.blade.php): precisa estar
     * acessível via $this-> porque islands não têm acesso a variáveis de
     * view vindas de fora deles, só a propriedades/métodos do componente.
     */
    #[Computed]
    public function topics()
    {
        return ForumTopic::with('author')->withCount('replies')->latest()->paginate(10);
    }

    public function render()
    {
        return view('livewire.site.forum.forum-index');
    }
}
