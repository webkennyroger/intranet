{{--
    Banner de boas-vindas do topo do Dashboard admin.
    Mostra uma saudação rápida + um recado opcional (ex.: quantos
    rascunhos estão pendentes) e, se precisar, botões de ação à direita
    via o slot "actions".
    O degradê é calculado a partir da cor "Primary" salva em Aparência
    (SiteSetting::heroGradientStops()) — se o admin trocar essa cor, o
    banner acompanha automaticamente. Os tons já saem prontos em hex do
    PHP (em vez de CSS color-mix()) porque um stop de gradiente que o
    navegador não resolve invalida o background inteiro, fazendo o
    degradê simplesmente não aparecer.
--}}
@props([
    'name',
    'message',
    'badge' => null,
])

@php
    [$stop0, $stop35, $stop65, $stop100] = \App\Models\SiteSetting::current()->heroGradientStops();
@endphp

<div
    class="relative overflow-hidden rounded-2xl p-7 sm:p-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-5"
    style="
        background: linear-gradient(135deg, {{ $stop0 }} 0%, {{ $stop35 }} 35%, {{ $stop65 }} 65%, {{ $stop100 }} 100%);
        color: #fff;
        border: 1px solid rgba(255, 255, 255, 0.12);
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2), 0 2px 8px rgba(0, 0, 0, 0.08);
    "
>
    {{-- Manchas translúcidas só de efeito visual, não interagem com nada --}}
    <div class="pointer-events-none absolute -top-20 -right-10 w-80 h-80 rounded-full" style="background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);"></div>
    <div class="pointer-events-none absolute -bottom-24 left-[15%] w-72 h-72 rounded-full" style="background: radial-gradient(circle, rgba(255, 255, 255, 0.06) 0%, transparent 70%);"></div>

    <div class="relative z-10 flex items-start gap-4">
        <div class="w-13 h-13 rounded-2xl flex items-center justify-center shrink-0 bg-white/15 border border-white/20 backdrop-blur-sm">
            <i class="fa-solid fa-house text-xl"></i>
        </div>
        <div>
            <h2 class="text-2xl font-bold tracking-tight leading-tight mb-1.5">Bem-vindo(a), {{ $name }}!</h2>
            <p class="text-sm opacity-90 leading-relaxed max-w-130">
                {{ $message }}
                @if ($badge)
                    <strong class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-bold bg-white/20 backdrop-blur-sm ml-1">{{ $badge }}</strong>
                @endif
            </p>
        </div>
    </div>

    @isset($actions)
        <div class="relative z-10 flex items-center gap-2.5 flex-wrap">
            {{ $actions }}
        </div>
    @endisset
</div>
