{{--
    Tela de Serviços.
    Lista os links de serviços/sistemas úteis para os servidores, cada
    um com ícone e abrindo em nova aba.
--}}
<div class="mx-auto space-y-4">
    <!-- Cabeçalho da página -->
    <x-site.page-header icon="sliders" title="Serviços" subtitle="Confira os serviços disponíveis." />

    @if ($links->isEmpty())
        <!-- Estado vazio -->
        <div class="ui-card p-12 text-center text-slate-500 dark:text-(--text-secondary)">
            Nenhum serviço cadastrado por enquanto.
        </div>
    @else
        <!-- Grade de links de serviços -->
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-2 lg:grid-cols-4 gap-x-6 gap-y-8">
            @foreach ($links as $link)
                <a href="{{ $link->url }}" target="_blank" rel="noopener" class="ui-card flex items-center gap-4 p-3 hover:shadow-md hover:bg-emerald-50/30 transition group">
                    <div class="w-14 h-14 rounded-full border-2 border-emerald-800 flex items-center justify-center shrink-0 bg-white dark:bg-(--bg-card) group-hover:scale-105 transition">
                        <i class="fa-solid fa-{{ $link->icon }} text-xl text-emerald-800"></i>
                    </div>
                    <span class="font-extrabold text-sm text-slate-800 dark:text-(--text-primary) leading-snug group-hover:text-emerald-800 transition">
                        {{ $link->title }}
                    </span>
                </a>
            @endforeach
        </div>
    @endif
</div>
