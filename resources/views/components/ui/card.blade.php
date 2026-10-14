{{--
    Cartão genérico com cantos arredondados e slots opcionais de "header" e
    "footer". Usado em várias telas do admin e do site para agrupar
    conteúdo. Visual no mesmo padrão da demo "Cards" do uicolors.app: sem
    borda sólida, raio de 20px e sombra colorida (classe .ui-card, ver
    app.css) em vez de borda — acompanha claro/escuro sozinho via CSS vars,
    sem precisar de classe dark: em cada tela. "tone" liga a variante de
    destaque (fundo sólido num tom da paleta — 300/400/500/600 — com texto
    branco), pra cards que precisam chamar mais atenção (resumo, CTA) em
    vez do fundo neutro.
--}}
@props([
    'padding' => true,
    'tone' => null,
])

@php
    $paddingClass = $padding ? 'p-6' : '';
@endphp

<div
    {{ $attributes->merge([
        'class' => 'ui-card' . ($tone ? ' ui-card-accent' : ''),
    ]) }}
    @if($tone) style="background-color: var(--color-primary-{{ $tone }});" @endif
>
    @if(isset($header))
        <div class="px-6 py-4" style="border-bottom: 1px solid var(--border-color);">
            {{ $header }}
        </div>
    @endif

    <div class="{{ $paddingClass }}">
        {{ $slot }}
    </div>

    @if(isset($footer))
        <div class="px-6 py-4" style="border-top: 1px solid var(--border-color);">
            {{ $footer }}
        </div>
    @endif
</div>
