<?php

namespace App\Livewire\Admin\Forum;

use App\Models\ForumReply;
use App\Models\ForumTopic;
use App\Services\ActivityLogger;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Tela admin "Fórum": lista os tópicos criados pelos usuários no fórum
 * interno, permitindo moderar excluindo tópicos indevidos.
 */
class AdminForumIndex extends Component
{
    use WithPagination;

    public string $search = '';

    /** @var array<int, int> */
    public array $selected = [];

    public function updatedSearch(): void
    {
        $this->resetPage();
        $this->selected = [];
    }

    private function filteredQuery()
    {
        return ForumTopic::when($this->search !== '', fn ($query) => $query->where(fn ($query) => $query
            ->where('title', 'like', "%{$this->search}%")
            ->orWhereHas('author', fn ($query) => $query->where('name', 'like', "%{$this->search}%"))));
    }

    public function delete(int $topicId): void
    {
        abort_unless(auth()->user()->can('forum.delete'), 403);

        $topic = ForumTopic::findOrFail($topicId);
        $topic->delete();
        ActivityLogger::deleted($topic);
        $this->dispatch('toast', title: 'Fórum', message: 'Tópico excluído com sucesso.', variant: 'danger');
    }

    public function toggleSelectAllOnPage(): void
    {
        $pageIds = $this->topics->pluck('id')->all();

        if (array_diff($pageIds, $this->selected) === []) {
            $this->selected = array_values(array_diff($this->selected, $pageIds));
        } else {
            $this->selected = array_values(array_unique(array_merge($this->selected, $pageIds)));
        }
    }

    public function deselectAll(): void
    {
        $this->selected = [];
    }

    public function bulkDelete(): void
    {
        abort_unless(auth()->user()->can('forum.delete'), 403);

        $topics = ForumTopic::whereIn('id', $this->selected)->get();

        foreach ($topics as $topic) {
            $topic->delete();
            ActivityLogger::deleted($topic);
        }

        $this->selected = [];
        $this->dispatch('toast', title: 'Fórum', message: $topics->count().' tópico(s) excluído(s) com sucesso.', variant: 'danger');
    }

    /**
     * Isolada num island (ver admin-forum-index.blade.php): precisa estar
     * acessível via $this-> porque islands não têm acesso a variáveis de
     * view vindas de fora deles, só a propriedades/métodos do componente.
     */
    #[Computed]
    public function topics()
    {
        return $this->filteredQuery()->with('author')->withCount('replies')->latest()->paginate(10);
    }

    #[Computed]
    public function allSelectedOnPage(): bool
    {
        return $this->topics->isNotEmpty() && array_diff($this->topics->pluck('id')->all(), $this->selected) === [];
    }

    public function render()
    {
        return view('livewire.admin.forum.admin-forum-index', [
            'totalTopics' => ForumTopic::count(),
            'totalReplies' => ForumReply::count(),
        ])->layout('layouts.admin', ['title' => 'Fórum']);
    }
}
