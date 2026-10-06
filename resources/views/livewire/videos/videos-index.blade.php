{{--
    Tela de Vídeos.
    Lista os vídeos institucionais em grade, cada um com player
    incorporado (iframe), título e descrição.
--}}
<div class="max-w-7xl mx-auto space-y-8">
    <!-- Selo da seção -->
    <div class="flex items-center">
        <div class="text-white text-xs font-extrabold uppercase px-4 py-1.5 rounded-full shadow-sm tracking-wider flex items-center gap-2" style="background-color: #0C4A34;">
            <i class="fa-solid fa-video text-xs"></i>
            <span>VÍDEOS</span>
        </div>
    </div>

    @if ($videos->isEmpty())
        <!-- Estado vazio -->
        <div class="bg-white border border-slate-200 rounded-2xl p-12 text-center text-slate-500">
            Nenhum vídeo disponível por enquanto.
        </div>
    @else
        <!-- Grade de vídeos -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach ($videos as $video)
                <div class="rounded-2xl bg-white border border-slate-200/80 shadow-sm overflow-hidden">
                    <div class="aspect-video bg-slate-100">
                        <iframe src="{{ $video->embedUrl() }}" class="w-full h-full" frameborder="0" allowfullscreen loading="lazy"></iframe>
                    </div>
                    <div class="p-4">
                        <span class="font-bold text-sm text-slate-800 block">{{ $video->title }}</span>
                        @if ($video->description)
                            <span class="text-xs text-slate-500 line-clamp-2 block mt-0.5">{{ $video->description }}</span>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
