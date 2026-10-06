{{--
    Tela de Serviços.
    Lista os links de serviços/sistemas úteis para os servidores, cada
    um com ícone e abrindo em nova aba.
--}}
<div class="max-w-7xl mx-auto space-y-8">
    <!-- Selo da seção -->
    <div class="flex items-center">
        <div class="bg-dpemt-dark text-white text-xs font-extrabold uppercase px-4 py-1.5 rounded-full shadow-sm tracking-wider flex items-center gap-2" style="background-color: #0C4A34;">
            <i class="fa-solid fa-sliders text-xs"></i>
            <span>SERVIÇOS</span>
        </div>
    </div>

    @if ($links->isEmpty())
        <!-- Estado vazio -->
        <div class="bg-white border border-slate-200 rounded-2xl p-12 text-center text-slate-500">
            Nenhum serviço cadastrado por enquanto.
        </div>
    @else
        <!-- Grade de links de serviços -->
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-2 lg:grid-cols-4 gap-x-6 gap-y-8">
            @foreach ($links as $link)
                <a href="{{ $link->url }}" target="_blank" rel="noopener" class="flex items-center gap-4 p-3 rounded-2xl bg-white border border-slate-200/80 shadow-sm hover:shadow-md hover:border-emerald-500/50 hover:bg-emerald-50/30 transition group">
                    <div class="w-14 h-14 rounded-full border-2 border-emerald-800 flex items-center justify-center shrink-0 bg-white group-hover:scale-105 transition">
                        <i class="fa-solid fa-{{ $link->icon }} text-xl text-emerald-800"></i>
                    </div>
                    <span class="font-extrabold text-sm text-slate-800 leading-snug group-hover:text-emerald-800 transition">
                        {{ $link->title }}
                    </span>
                </a>
            @endforeach
        </div>
    @endif
</div>
