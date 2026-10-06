{{--
    Tela de Notícias.
    Lista os comunicados e informativos oficiais da Defensoria em
    formato de linha do tempo, com link para a matéria completa.
--}}
<div class="max-w-4xl mx-auto space-y-6">
    <!-- Cabeçalho da página -->
    <div>
        <h1 class="text-3xl font-black text-slate-800 tracking-tight">Notícias</h1>
        <p class="text-slate-500 text-sm mt-1 font-medium">Comunicados e informativos oficiais da Defensoria.</p>
    </div>

    @if ($articles->isEmpty())
        <!-- Estado vazio -->
        <div class="bg-white border border-slate-200 rounded-2xl p-12 text-center text-slate-500">
            Nenhuma notícia publicada por enquanto.
        </div>
    @else
        <!-- Linha do tempo de notícias -->
        <div class="relative border-l-2 border-emerald-100 ml-3 space-y-6">
            @foreach ($articles as $article)
                <div class="relative pl-8">
                    <span class="absolute -left-[9px] top-1.5 w-4 h-4 rounded-full border-2 border-white" style="background-color: #0C4A34;"></span>
                    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 hover:shadow-md transition">
                        <span class="text-xs font-semibold text-emerald-700 uppercase tracking-wide">{{ $article->published_at?->format('d/m/Y') }}</span>
                        <h2 class="font-extrabold text-slate-800 mt-1">{{ $article->title }}</h2>
                        <p class="text-sm text-slate-600 mt-1 line-clamp-2">{{ $article->excerpt }}</p>
                        <a href="{{ route('mural.show', $article) }}" class="text-emerald-700 font-bold text-xs hover:underline inline-flex items-center gap-1 mt-3">
                            Ler notícia completa <i class="fa-solid fa-arrow-right text-[10px]"></i>
                        </a>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Paginação -->
        <div class="pt-4">
            {{ $articles->links() }}
        </div>
    @endif
</div>
