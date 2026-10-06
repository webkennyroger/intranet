{{--
    Tela do Fórum (listagem de tópicos).
    Mostra os tópicos de discussão criados pelos servidores e permite
    abrir o modal para criar um novo tópico.
--}}
<div class="max-w-4xl mx-auto space-y-6">
    <!-- Cabeçalho da página -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-black text-slate-800 tracking-tight">Fórum</h1>
            <p class="text-slate-500 text-sm mt-1 font-medium">Discussões e sugestões entre servidores.</p>
        </div>
        <x-ui.button x-on:click="window.dispatchEvent(new CustomEvent('open-modal', { detail: 'new-topic' }))">
            Novo tópico
        </x-ui.button>
    </div>

    @if ($topics->isEmpty())
        <!-- Estado vazio -->
        <div class="bg-white border border-slate-200 rounded-2xl p-12 text-center text-slate-500">
            Nenhum tópico criado ainda. Seja o primeiro a iniciar uma discussão!
        </div>
    @else
        <!-- Lista de tópicos -->
        <div class="space-y-3">
            @foreach ($topics as $topic)
                <a href="{{ route('forum.show', $topic) }}" class="flex items-center gap-4 p-4 rounded-2xl bg-white border border-slate-200/80 shadow-sm hover:shadow-md hover:border-emerald-500/50 transition">
                    <x-ui.avatar :name="$topic->author->name" size="md" />
                    <div class="min-w-0 flex-1">
                        <span class="font-bold text-sm text-slate-800 block truncate">{{ $topic->title }}</span>
                        <span class="text-xs text-slate-500">por {{ $topic->author->name }} · {{ $topic->created_at->format('d/m/Y H:i') }}</span>
                    </div>
                    <div class="text-center shrink-0">
                        <span class="block font-bold text-emerald-700">{{ $topic->replies_count }}</span>
                        <span class="block text-[10px] text-slate-400 uppercase">respostas</span>
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
                <textarea wire:model="body" rows="5" class="w-full rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500/40 focus:border-emerald-500 transition-all duration-200 px-4 py-2.5 text-sm" style="background-color: var(--bg-input); border: 1px solid var(--border-color); color: var(--text-primary);"></textarea>
                @error('body') <p class="text-xs text-red-400">{{ $message }}</p> @enderror
            </div>
            <x-ui.button type="submit">Publicar tópico</x-ui.button>
        </form>
    </x-ui.modal>
</div>
