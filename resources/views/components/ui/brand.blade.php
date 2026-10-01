@props([
    'name' => null,
    'logo' => null,
    'href' => null,
    'alt' => null,
    'size' => 'md',
])

@php
    $displayName = $name ?? config('app.name', 'App');

    // `logo` pode chegar como slot nomeado (<x-slot:logo>) ou como atributo
    // string (URL da imagem) — ambos usam o mesmo nome, como na Flux UI.
    $logoSlot = ($logo instanceof \Illuminate\View\ComponentSlot && $logo->isNotEmpty()) ? $logo : null;
    $logoUrl = is_string($logo) ? $logo : null;

    $boxSizes = [
        'sm' => 'h-8 w-8',
        'md' => 'h-10 w-10',
        'lg' => 'h-12 w-12',
    ];
    $markSizes = [
        'sm' => 'text-sm',
        'md' => 'text-xl',
        'lg' => 'text-2xl',
    ];
    $labelSizes = [
        'sm' => 'text-xs',
        'md' => 'text-sm',
        'lg' => 'text-base',
    ];

    $boxClass = $boxSizes[$size] ?? $boxSizes['md'];
    $markClass = $markSizes[$size] ?? $markSizes['md'];
    $labelClass = $labelSizes[$size] ?? $labelSizes['md'];

    $tag = $href ? 'a' : 'div';
@endphp

<{{ $tag }}
    @if($href) href="{{ $href }}" @endif
    {{ $attributes->merge(['class' => 'flex items-center gap-3 overflow-hidden']) }}
>
    <div class="{{ $boxClass }} rounded-xl bg-gradient-to-tr from-emerald-600 via-teal-500 to-cyan-400 p-0.5 shadow-lg shadow-emerald-500/20 flex-shrink-0">
        <div class="w-full h-full rounded-[10px] flex items-center justify-center overflow-hidden" style="background-color: var(--bg-base);">
            @if($logoSlot)
                {{ $logoSlot }}
            @elseif($logoUrl)
                <img src="{{ $logoUrl }}" alt="{{ $alt ?? $displayName }}" class="w-full h-full object-contain" />
            @else
                <span class="text-transparent bg-clip-text bg-gradient-to-tr from-emerald-400 to-cyan-300 font-bold {{ $markClass }}">{{ mb_strtoupper(mb_substr($displayName, 0, 1)) }}</span>
            @endif
        </div>
    </div>

    @if($displayName)
        <span class="font-bold {{ $labelClass }} whitespace-nowrap truncate" style="color: var(--text-primary);">{{ $displayName }}</span>
    @endif
</{{ $tag }}>
