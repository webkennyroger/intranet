@props([
    'padding' => true,
])

@php
    $paddingClass = $padding ? 'p-6' : '';
@endphp

<div {{ $attributes->merge(['class' => 'rounded-2xl']) }} style="background-color: var(--bg-card); border: 1px solid var(--border-color);">
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
