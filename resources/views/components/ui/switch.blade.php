@props([
    'label' => null,
    'description' => null,
    'checked' => false,
    'align' => 'right',
])

@php
    $switchOrder = $align === 'left' ? 'order-first' : 'order-last';
@endphp

<label class="inline-flex w-full items-center justify-between gap-3 cursor-pointer select-none">
    @if($label || $description || $slot->isNotEmpty())
        <span class="text-sm leading-5">
            @if($label || $slot->isNotEmpty())
                <span class="block font-medium" style="color: var(--text-primary);">{{ $label ?? $slot }}</span>
            @endif
            @if($description)
                <span class="block mt-0.5" style="color: var(--text-muted);">{{ $description }}</span>
            @endif
        </span>
    @endif

    <span class="relative inline-flex h-6 w-11 shrink-0 items-center {{ $switchOrder }}">
        <input
            type="checkbox"
            @checked($checked)
            {{ $attributes->merge(['class' => 'peer sr-only']) }}
        />

        <span
            class="absolute inset-0 rounded-full border transition-colors duration-200 bg-[var(--bg-elevated)] border-[var(--border-color)] peer-checked:bg-emerald-500 peer-checked:border-emerald-500 peer-focus-visible:ring-2 peer-focus-visible:ring-emerald-500/40"
            aria-hidden="true"
        ></span>

        <span
            class="absolute left-0.5 top-0.5 h-4 w-4 rounded-full bg-white shadow transition-transform duration-200 peer-checked:translate-x-5"
            aria-hidden="true"
        ></span>
    </span>
</label>
