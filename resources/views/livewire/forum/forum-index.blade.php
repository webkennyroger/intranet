{{--
    Tela do Fórum (listagem de tópicos).
    Mostra os tópicos de discussão criados pelos servidores e permite
    abrir o modal para criar um novo tópico.
--}}
<div class="space-y-4">
    <!-- Cabeçalho da página -->
    <x-site.page-header icon="comments" title="Fórum" subtitle="Discussões e sugestões entre servidores.">
        <x-slot:actions>
            <x-ui.button x-on:click="window.dispatchEvent(new CustomEvent('open-modal', { detail: 'new-topic' }))">
                Novo tópico
            </x-ui.button>
        </x-slot:actions>
    </x-site.page-header>

    @if ($topics->isEmpty())
        <!-- Estado vazio -->
        <div class="ui-card p-12 text-center text-slate-500 dark:text-(--text-secondary)">
            Nenhum tópico criado ainda. Seja o primeiro a iniciar uma discussão!
        </div>
    @else
        <!-- Lista de tópicos -->
        <div class="space-y-3">
            @foreach ($topics as $topic)
                <a href="{{ route('forum.show', $topic) }}" class="ui-card flex items-center gap-4 p-4 hover:shadow-md transition">
                    <x-ui.avatar :name="$topic->author->name" size="md" />
                    <div class="min-w-0 flex-1">
                        <span class="font-bold text-sm text-slate-800 dark:text-(--text-primary) block truncate">{{ $topic->title }}</span>
                        <span class="text-xs text-slate-500 dark:text-(--text-secondary)">por {{ $topic->author->name }} · {{ $topic->created_at->format('d/m/Y H:i') }}</span>
                    </div>
                    <div class="text-center shrink-0">
                        <span class="block font-bold text-emerald-700">{{ $topic->replies_count }}</span>
                        <span class="block text-[10px] text-slate-400 dark:text-(--text-muted) uppercase">respostas</span>
                    </div>
                </a>
            @endforeach
        </div>

        <!-- Paginação -->
        <div class="pt-4">
            {{ $topics->links() }}
        </div>
    @endif

    <!-- Modal de novo tópico -->
    <x-ui.modal name="new-topic" maxWidth="lg">
        <x-slot:header>Novo tópico</x-slot:header>
        <form wire:submit="createTopic" class="space-y-4">
            <x-ui.input wire:model="title" label="Título" />
            <div class="space-y-1.5">
                <label class="block text-sm font-medium" style="color: var(--text-secondary);">Mensagem</label>
                <textarea wire:model="body" rows="5" class="w-full rounded-xl focus:outline-none focus:ring-2 focus:ring-(--focus-ring)/40 focus:border-(--focus-ring) transition-all duration-200 px-4 py-2.5 text-sm" style="background-color: var(--bg-input); border: 1px solid var(--border-color); color: var(--text-primary);"></textarea>
                @error('body') <p class="text-xs text-red-400">{{ $message }}</p> @enderror
            </div>
            <x-ui.button type="submit">Publicar tópico</x-ui.button>
        </form>
    </x-ui.modal>
</div>
