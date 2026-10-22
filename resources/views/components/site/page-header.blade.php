{{--
    Cabeçalho padrão de toda página do site (público): ícone, título,
    subtítulo opcional, um slot "actions" pra botões extras (ex.: "Novo
    tópico" no Fórum) e, por fim, o botão "Voltar" — por padrão leva à
    Início, mas aceita uma rota própria via "back" para páginas de detalhe
    (ex.: voltar para o álbum/categoria de onde o usuário veio). O ícone da
    própria página aparece de novo, enorme e bem apagado, como marca-d'água
    decorativa — acompanha automaticamente qualquer página que já passa um
    "icon", sem precisar de prop nova.
--}}
@props([
    'icon' => null,
    'title',
    'subtitle' => null,
    'back' => null,
    'iconBg' => null,
    'iconColor' => 'text-white',
])

@php
    $accent = 'var(--brand-primary, #0C4A34)';
    $iconBg ??= "background: linear-gradient(135deg, {$accent}, color-mix(in srgb, {$accent} 70%, black));";
@endphp

<div class="ui-card relative overflow-hidden p-6" style="border-top: 3px solid {{ $accent }};">
    @if ($icon)
        <i class="fa-solid fa-{{ $icon }} absolute -right-4 -top-6 text-[9rem] pointer-events-none select-none" style="color: {{ $accent }}; opacity: 0.06;" aria-hidden="true"></i>
    @endif

    <div class="relative z-10 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-4">
            @if ($icon)
                <span class="w-14 h-14 rounded-2xl flex items-center justify-center shrink-0" style="{{ $iconBg }} box-shadow: 0 8px 16px -4px color-mix(in srgb, {{ $accent }} 50%, transparent);">
                    <i class="fa-solid fa-{{ $icon }} text-xl {{ $iconColor }}"></i>
                </span>
            @endif
            <div>
                <h1 class="text-3xl font-black text-slate-800 dark:text-(--text-primary) tracking-tight">{{ $title }}</h1>
                @if ($subtitle)
                    <p class="text-slate-500 dark:text-(--text-secondary) text-sm mt-0.5 font-medium">{{ $subtitle }}</p>
                @endif
            </div>
        </div>

        <div class="flex items-center gap-2 shrink-0">
            @isset($actions)
                {{ $actions }}
            @endisset

            <a href="{{ $back ?? route('inicio') }}" class="shrink-0 inline-flex items-center gap-2 rounded-xl px-5 py-2.5 text-sm font-bold transition hover:brightness-95 active:brightness-90" style="background-color: color-mix(in srgb, {{ $accent }} 10%, transparent); color: {{ $accent }}; border: 1.5px solid color-mix(in srgb, {{ $accent }} 30%, transparent);">
                <i class="fa-solid fa-arrow-left text-xs"></i> Voltar
            </a>
        </div>
    </div>
</div>
