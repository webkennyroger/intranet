@props([
    'country' => null,
    'src' => null,
    'alt' => '',
    'size' => 'sm',
    'circle' => false,
])

@php
    // Tamanhos em px, iguais aos da Flux UI (xs=20, sm=24, md=32, lg=40, xl=48).
    $pixelSizes = [
        'xs' => 20,
        'sm' => 24,
        'md' => 32,
        'lg' => 40,
        'xl' => 48,
    ];
    $px = $pixelSizes[$size] ?? $pixelSizes['sm'];

    // Sem pacote de bandeiras SVG disponível: convertemos o código ISO 3166-1
    // alpha-2 (br, us, es, ...) para o emoji de bandeira correspondente,
    // combinando os dois "regional indicator symbols" Unicode (U+1F1E6..U+1F1FF).
    // Isso cobre qualquer código válido, não só um mapa fixo de 3 países.
    $code = strtoupper(preg_replace('/[^a-zA-Z]/', '', (string) $country));
    $flagEmoji = null;

    if (strlen($code) === 2) {
        $flagEmoji = mb_chr(0x1F1E6 + (ord($code[0]) - 65), 'UTF-8')
            . mb_chr(0x1F1E6 + (ord($code[1]) - 65), 'UTF-8');
    }

    $ariaLabel = $alt !== '' ? $alt : ($code ? 'Bandeira: ' . $code : 'Bandeira desconhecida');
@endphp

<span
    {{ $attributes->merge(['class' => 'inline-flex items-center justify-center flex-shrink-0 select-none leading-none ' . ($circle ? 'rounded-full' : 'rounded-sm')]) }}
    style="width: {{ $px }}px; height: {{ $px }}px; font-size: {{ (int) round($px * 0.75) }}px; @if($circle) background-color: var(--bg-elevated); border: 1px solid var(--border-color); overflow: hidden; @endif"
    role="img"
    aria-label="{{ $ariaLabel }}"
    @if($alt !== '') title="{{ $alt }}" @endif
>
    @if($src)
        <img src="{{ $src }}" alt="{{ $alt }}" class="w-full h-full object-cover {{ $circle ? 'rounded-full' : 'rounded-sm' }}" />
    @elseif($flagEmoji)
        {{ $flagEmoji }}
    @else
        🏳️
    @endif
</span>
