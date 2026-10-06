<?php

namespace App\Livewire\Forum;

use App\Models\ForumTopic;
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

        $this->redirect(route('forum.show', $topic));
    }

    public function render()
    {
        return view('livewire.forum.forum-index', [
            'topics' => ForumTopic::with('author')->withCount('replies')->latest()->paginate(10),
        ]);
    }
}
