{{--
    Tela SOS DPEMT.
    Lista avisos e informativos urgentes do órgão em destaque (fundo
    vermelho), com link para a matéria completa.
--}}
<div class="max-w-4xl mx-auto space-y-6">
    <!-- Cabeçalho da página -->
    <div class="flex items-center gap-3">
        <div class="w-12 h-12 rounded-xl bg-red-100 flex items-center justify-center shrink-0">
            <i class="fa-solid fa-triangle-exclamation text-xl text-red-600"></i>
        </div>
        <div>
            <h1 class="text-3xl font-black text-slate-800 tracking-tight">SOS DPEMT</h1>
            <p class="text-slate-500 text-sm mt-1 font-medium">Avisos e informativos urgentes do órgão.</p>
        </div>
    </div>

    @if ($articles->isEmpty())
        <!-- Estado vazio -->
        <div class="bg-white border border-slate-200 rounded-2xl p-12 text-center text-slate-500">
            Nenhum informativo urgente no momento.
        </div>
    @else
        <!-- Lista de avisos urgentes -->
        <div class="space-y-4">
            @foreach ($articles as $article)
                <div class="flex items-start gap-4 p-5 rounded-2xl bg-red-50 border border-red-200">
                    <i class="fa-solid fa-circle-exclamation text-red-500 text-lg mt-0.5"></i>
                    <div class="min-w-0">
                        <span class="text-xs font-semibold text-red-600 uppercase tracking-wide">{{ $article->published_at?->format('d/m/Y') }}</span>
                        <h2 class="font-extrabold text-slate-800 mt-1">{{ $article->title }}</h2>
                        <p class="text-sm text-slate-600 mt-1">{{ $article->excerpt }}</p>
                        <a href="{{ route('mural.show', $article) }}" class="text-red-700 font-bold text-xs hover:underline inline-flex items-center gap-1 mt-3">
                            Ver detalhes <i class="fa-solid fa-arrow-right text-[10px]"></i>
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
