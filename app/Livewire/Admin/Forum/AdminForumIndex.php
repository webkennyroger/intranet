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

    public function delete(int $topicId): void
    {
        $topic = ForumTopic::findOrFail($topicId);
        $topic->delete();
        ActivityLogger::deleted($topic);
    }

    public function render()
    {
        return view('livewire.admin.forum.admin-forum-index', [
            'topics' => ForumTopic::with('author')->withCount('replies')->latest()->paginate(10),
            'totalTopics' => ForumTopic::count(),
            'totalReplies' => ForumReply::count(),
        ])->layout('layouts.admin', ['title' => 'Fórum']);
    }
}
