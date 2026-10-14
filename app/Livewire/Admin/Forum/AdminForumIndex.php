<?php

namespace App\Livewire\Admin\Forum;

use App\Models\ForumReply;
use App\Models\ForumTopic;
use App\Services\ActivityLogger;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Tela admin "Fórum": lista os tópicos criados pelos usuários no fórum
 * interno, permitindo moderar excluindo tópicos indevidos.
 */
class AdminForumIndex extends Component
{
    use WithPagination;

    /** @var array<int, int> */
    public array $selected = [];

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
        $pageIds = ForumTopic::latest()->paginate(10)->pluck('id')->all();

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

    public function render()
    {
        $topics = ForumTopic::with('author')->withCount('replies')->latest()->paginate(10);

        return view('livewire.admin.forum.admin-forum-index', [
            'topics' => $topics,
            'allSelectedOnPage' => $topics->isNotEmpty() && array_diff($topics->pluck('id')->all(), $this->selected) === [],
            'totalTopics' => ForumTopic::count(),
            'totalReplies' => ForumReply::count(),
        ])->layout('layouts.admin', ['title' => 'Fórum']);
    }
}
