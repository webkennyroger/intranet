<?php

namespace App\Livewire\Forum;

use App\Models\ForumReply;
use App\Models\ForumTopic;
use Livewire\Attributes\Validate;
use Livewire\Component;

/**
 * Página de um tópico do fórum: mostra as respostas, permite ao autor
 * responder, editar ou excluir, e dá a administradores poderes de
 * moderação (excluir tópico ou resposta de qualquer usuário).
 */
class ForumShow extends Component
{
    public ForumTopic $topic;

    #[Validate('required|string|max:5000')]
    public string $replyBody = '';

    public ?int $editingReplyId = null;

    public string $editingReplyBody = '';

    public function mount(ForumTopic $topic): void
    {
        $this->topic = $topic;
    }

    private function canModerate(): bool
    {
        return auth()->user()->hasAnyRole(['Super Administrador', 'Administrador']);
    }

    public function addReply(): void
    {
        $this->validate();

        ForumReply::create([
            'forum_topic_id' => $this->topic->id,
            'author_id' => auth()->id(),
            'body' => $this->replyBody,
        ]);

        $this->reset(['replyBody']);
    }

    public function deleteTopic(): void
    {
        abort_unless($this->topic->author_id === auth()->id() || $this->canModerate(), 403);

        $this->topic->delete();

        $this->redirect(route('forum.index'));
    }

    public function editReply(int $replyId): void
    {
        $reply = ForumReply::findOrFail($replyId);
        abort_unless($reply->author_id === auth()->id(), 403);

        $this->editingReplyId = $replyId;
        $this->editingReplyBody = $reply->body;
    }

    public function cancelEditReply(): void
    {
        $this->editingReplyId = null;
        $this->editingReplyBody = '';
    }

    public function updateReply(): void
    {
        $reply = ForumReply::findOrFail($this->editingReplyId);
        abort_unless($reply->author_id === auth()->id(), 403);

        $this->validate(['editingReplyBody' => 'required|string|max:5000']);

        $reply->update(['body' => $this->editingReplyBody]);
        $this->cancelEditReply();
    }

    public function deleteReply(int $replyId): void
    {
        $reply = ForumReply::findOrFail($replyId);
        abort_unless($reply->author_id === auth()->id() || $this->canModerate(), 403);

        $reply->delete();
    }

    public function render()
    {
        return view('livewire.forum.forum-show', [
            'replies' => $this->topic->replies()->with('author')->get(),
            'canModerate' => $this->canModerate(),
        ]);
    }
}
