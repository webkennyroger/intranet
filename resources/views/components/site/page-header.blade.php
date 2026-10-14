{{--
    Cabeçalho padrão de toda página do site (público): ícone, título,
    subtítulo opcional, um slot "actions" pra botões extras (ex.: "Novo
    tópico" no Fórum) e, por fim, o botão "Voltar" — por padrão leva à
    Início, mas aceita uma rota própria via "back" para páginas de detalhe
    (ex.: voltar para o álbum/categoria de onde o usuário veio).
--}}
@props([
    'icon' => null,
    'title',
    'subtitle' => null,
    'back' => null,
    'iconBg' => 'background-color: var(--brand-primary, #0C4A34);',
    'iconColor' => 'text-white',
])

<div class="ui-card p-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div class="flex items-center gap-4">
        @if ($icon)
            <span class="w-14 h-14 rounded-2xl flex items-center justify-center shrink-0 shadow-sm" style="{{ $iconBg }}">
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

        <a href="{{ $back ?? route('inicio') }}" class="shrink-0 inline-flex items-center gap-2 rounded-xl px-5 py-2.5 text-sm font-bold text-white shadow-sm hover:brightness-110 active:brightness-95 transition" style="background-color: var(--brand-primary, #0C4A34);">
            <i class="fa-solid fa-arrow-left text-xs"></i> Voltar
        </a>
    </div>
</div>
