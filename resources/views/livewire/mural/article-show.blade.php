{{--
    Tela de leitura de uma matéria do Mural.
    Mostra a capa, o conteúdo completo da notícia/artigo, a data de
    publicação e permite curtir a matéria.
--}}
<div class="max-w-3xl mx-auto space-y-6">
    <a href="{{ route('mural.index') }}" class="text-emerald-700 text-sm font-bold hover:underline inline-flex items-center gap-1">
        <i class="fa-solid fa-arrow-left text-[10px]"></i> Voltar ao Mural
    </a>

    <!-- Conteúdo da matéria -->
    <div class="rounded-2xl overflow-hidden border border-slate-200">
        <div class="h-52 flex items-center justify-center" style="background-color: {{ $article->cover_color }};">
            <i class="fa-solid fa-{{ $article->cover_icon }} text-4xl text-white"></i>
        </div>
        <div class="p-6 bg-white space-y-4">
            <div class="flex items-center justify-between text-xs text-slate-400">
                <button wire:click="toggleLike" class="flex items-center gap-1.5 {{ $article->isLikedBy(auth()->user()) ? 'text-emerald-700' : 'text-slate-500' }} hover:text-emerald-700 font-medium">
                    <i class="fa-{{ $article->isLikedBy(auth()->user()) ? 'solid' : 'regular' }} fa-thumbs-up"></i>
                    <span>{{ $article->likedByUsers()->count() }}</span>
                </button>
                <span>{{ $article->published_at?->format('d/m/Y') }}</span>
            </div>
            <h1 class="text-2xl font-black text-slate-800">{{ $article->title }}</h1>
            <div class="prose prose-sm max-w-none">{!! $article->body !!}</div>
        </div>
    </div>
</div>
