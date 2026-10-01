@props([
    'icon' => null,
    'href' => null,
    'danger' => false,
    'disabled' => false,
])

{{-- Item individual de um <x-ui.context>. Renderiza <a> quando `href` é
    informado (e não está desabilitado) ou <button> caso contrário, pra
    permitir tanto navegação quanto ações via wire:click/@click. --}}

@php
    $colorStyle = $danger ? 'color: rgb(248, 113, 113);' : 'color: var(--text-primary);';
    $baseClasses = 'flex w-full items-center gap-2.5 px-3.5 py-2 text-left text-sm transition-colors duration-100';
@endphp

@if($href && ! $disabled)
    <a
        href="{{ $href }}"
        role="menuitem"
        {{ $attributes->merge(['class' => $baseClasses . ' hover:bg-[var(--bg-hover)]']) }}
        style="{{ $colorStyle }}"
    >
        @if($icon)
            <x-ui.icon :name="$icon" class="w-4 h-4 shrink-0" />
        @endif
        <span class="flex-1 truncate">{{ $slot }}</span>
    </a>
@else
    <button
        type="button"
        role="menuitem"
        @if($disabled) disabled @endif
        {{ $attributes->merge(['class' => $baseClasses . ' ' . ($disabled ? 'opacity-50 cursor-not-allowed' : 'hover:bg-[var(--bg-hover)]')]) }}
        style="{{ $colorStyle }}"
    >
        @if($icon)
            <x-ui.icon :name="$icon" class="w-4 h-4 shrink-0" />
        @endif
        <span class="flex-1 truncate">{{ $slot }}</span>
    </button>
@endif
