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
        {{-- Cada atalho é um sistema diferente — por isso o ícone circula pela
             mesma paleta de 26 cores da tela de Aparência (sem Branco/Preto,
             que não funcionam como destaque de ícone), determinística pela
             posição, sem precisar de campo novo no banco — feito "hub de
             aplicativos" em vez de poucos tons se repetindo o tempo todo. Com
             26 cores pra, no máximo, 24 atalhos, quase nenhuma repete. --}}
        @php
            $accentPalette = [
                '#FB2C36', '#FF6900', '#FE9A00', '#F0B100', '#7CCF00', '#00C950', '#00BC7D',
                '#00BBA7', '#00B8DB', '#00A6F4', '#2B7FFF', '#615FFF', '#8E51FF', '#AD46FF',
                '#E12AFB', '#F6339A', '#FF2056', '#62748E', '#6A7282', '#71717B', '#737373',
                '#79716B', '#7C6D67', '#79697B', '#67787C', '#7C7C67',
            ];
        @endphp

        <!-- Grade de links de serviços -->
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-2 lg:grid-cols-4 gap-4">
            @foreach ($links as $link)
                @php $accent = $accentPalette[$loop->index % count($accentPalette)]; @endphp
                <a
                    href="{{ $link->url }}"
                    target="_blank"
                    rel="noopener"
                    class="ui-card relative overflow-hidden flex items-center gap-4 p-4 transition hover:-translate-y-0.5 hover:shadow-lg group"
                >
                    <span class="absolute left-0 top-0 bottom-0 w-1 scale-y-0 group-hover:scale-y-100 transition-transform" style="background-color: {{ $accent }};"></span>

                    <div class="w-14 h-14 rounded-2xl flex items-center justify-center shrink-0 transition group-hover:scale-105" style="background-color: color-mix(in srgb, {{ $accent }} 12%, transparent);">
                        <i class="fa-solid fa-{{ $link->icon }} text-xl" style="color: {{ $accent }};"></i>
                    </div>
                    <span class="font-bold text-sm leading-snug" style="color: var(--text-primary);">
                        {{ $link->title }}
                    </span>

                    <i class="fa-solid fa-arrow-up-right-from-square absolute top-3 right-3 text-[10px] opacity-0 group-hover:opacity-50 transition" style="color: {{ $accent }};"></i>
                </a>
            @endforeach
        </div>
    @endif
</div>
