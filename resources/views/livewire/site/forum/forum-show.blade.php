{{--
    Tela de detalhe de um tópico do Fórum.
    Exibe o tópico completo e suas respostas, e permite excluir o
    tópico (autor ou moderador) e responder a ele.
--}}
<div class="space-y-4">
    <!-- Cabeçalho da página -->
    <x-site.page-header icon="comments" :title="$topic->title" :back="route('forum.index')" />

    <!-- Tópico (post principal) -->
    <div class="ui-card p-6">
        <div class="flex items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <x-ui.avatar :name="$topic->author->name" size="md" />
                <div>
                    <span class="font-bold text-sm text-slate-800 dark:text-(--text-primary) block">{{ $topic->author->name }}</span>
                    <span class="text-xs text-slate-500 dark:text-(--text-secondary)">{{ $topic->created_at->format('d/m/Y H:i') }}</span>
                </div>
            </div>
            @if ($topic->author_id === auth()->id() || $canModerate)
                <button @click="confirmAction('Confirma a exclusão deste tópico e de todas as respostas?', () => $wire.deleteTopic())" class="text-sm text-red-600 hover:underline">
                    Excluir tópico
                </button>
            @endif
        </div>
        <p class="text-sm text-slate-700 dark:text-(--text-secondary) mt-2 whitespace-pre-line">{{ $topic->body }}</p>
    </div>

    <!-- Lista de respostas -->
    <div class="space-y-3">
        <h2 class="font-bold text-slate-700 dark:text-(--text-secondary) text-sm">{{ $replies->count() }} resposta(s)</h2>

        @foreach ($replies as $reply)
            <div class="ui-card p-4" wire:key="reply-{{ $reply->id }}">
                <div class="flex items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <x-ui.avatar :name="$reply->author->name" size="sm" />
                        <div>
                            <span class="font-semibold text-sm text-slate-800 dark:text-(--text-primary) block">{{ $reply->author->name }}</span>
                            <span class="text-xs text-slate-500 dark:text-(--text-secondary)">{{ $reply->created_at->format('d/m/Y H:i') }}</span>
                        </div>
                    </div>
                    @if ($reply->author_id === auth()->id())
                        <div class="space-x-2 text-xs">
                            <button wire:click="editReply({{ $reply->id }})" class="text-emerald-700 hover:underline">Editar</button>
                            <button @click="confirmAction('Confirma a exclusão desta resposta?', () => $wire.deleteReply({{ $reply->id }}))" class="text-red-600 hover:underline">Excluir</button>
                        </div>
                    @elseif ($canModerate)
                        <button @click="confirmAction('Confirma a exclusão desta resposta?', () => $wire.deleteReply({{ $reply->id }}))" class="text-xs text-red-600 hover:underline">Excluir</button>
                    @endif
                </div>

                @if ($editingReplyId === $reply->id)
                    <div class="mt-3 space-y-2">
                        <textarea wire:model="editingReplyBody" rows="3" class="w-full rounded-xl focus:outline-none focus:ring-2 focus:ring-(--focus-ring)/40 focus:border-(--focus-ring) transition-all duration-200 px-4 py-2.5 text-sm" style="background-color: var(--bg-input); border: 1px solid var(--border-color); color: var(--text-primary);"></textarea>
                        <div class="space-x-2">
                            <x-ui.button wire:click="updateReply" size="sm">Salvar</x-ui.button>
                            <button wire:click="cancelEditReply" class="text-xs text-slate-500 dark:text-(--text-secondary) hover:underline">Cancelar</button>
                        </div>
                    </div>
                @else
                    <p class="text-sm text-slate-700 dark:text-(--text-secondary) mt-2 whitespace-pre-line">{{ $reply->body }}</p>
                @endif
            </div>
        @endforeach
    </div>

    <!-- Formulário de resposta -->
    <div class="ui-card p-5">
        <h2 class="font-bold text-slate-700 dark:text-(--text-secondary) text-sm mb-3">Responder</h2>
        <form wire:submit="addReply" class="space-y-3">
            <textarea wire:model="replyBody" rows="3" placeholder="Escreva sua resposta..." class="w-full rounded-xl focus:outline-none focus:ring-2 focus:ring-(--focus-ring)/40 focus:border-(--focus-ring) transition-all duration-200 px-4 py-2.5 text-sm" style="background-color: var(--bg-input); border: 1px solid var(--border-color); color: var(--text-primary);"></textarea>
            @error('replyBody') <p class="text-xs text-red-400">{{ $message }}</p> @enderror
            <x-ui.button type="submit">Enviar resposta</x-ui.button>
        </form>
    </div>
</div>
