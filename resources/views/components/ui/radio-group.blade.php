@props([
    'label' => null,
    'description' => null,
    'error' => null,
    'variant' => 'default',
])

@php
    $layout = $variant === 'horizontal'
        ? 'flex flex-wrap items-start gap-x-6 gap-y-3'
        : 'flex flex-col gap-3';
@endphp

<fieldset {{ $attributes->merge(['class' => 'space-y-2 border-0 p-0 m-0']) }}>
    @if($label)
        <legend class="text-sm font-medium p-0 mb-1" style="color: var(--text-secondary);">{{ $label }}</legend>
    @endif

    @if($description)
        <p class="text-xs -mt-1 mb-2" style="color: var(--text-muted);">{{ $description }}</p>
    @endif

    <div class="{{ $layout }}">
        {{ $slot }}
    </div>

    @if($error)
        <p class="text-xs text-red-400 mt-1.5">{{ $error }}</p>
    @endif
</fieldset>
