@props([
    'label' => null,
    'description' => null,
    'value' => null,
    'error' => null,
    'checked' => false,
])

@php
    $hasError = filled($error);
    $boxBorder = $hasError ? 'border-red-500/50' : 'border-[var(--border-color)]';
@endphp

<div>
    <label class="inline-flex items-start gap-3 cursor-pointer select-none">
        <span class="relative inline-flex h-5 w-5 shrink-0 mt-0.5">
            <input
                type="radio"
                @if(! is_null($value)) value="{{ $value }}" @endif
                @checked($checked)
                {{ $attributes->merge(['class' => 'peer sr-only']) }}
            />

            <span
                class="absolute inset-0 rounded-full border bg-[var(--bg-input)] {{ $boxBorder }} transition-colors duration-150 peer-checked:border-emerald-500 peer-focus-visible:ring-2 peer-focus-visible:ring-emerald-500/40"
                aria-hidden="true"
            ></span>

            <span
                class="absolute inset-0 m-auto h-2.5 w-2.5 rounded-full bg-emerald-500 opacity-0 scale-50 transition-all duration-150 peer-checked:opacity-100 peer-checked:scale-100"
                aria-hidden="true"
            ></span>
        </span>

        @if($label || $slot->isNotEmpty())
            <span class="text-sm leading-5">
                <span class="block font-medium" style="color: var(--text-primary);">{{ $label ?? $slot }}</span>
                @if($description)
                    <span class="block mt-0.5" style="color: var(--text-muted);">{{ $description }}</span>
                @endif
            </span>
        @endif
    </label>

    @if($error)
        <p class="text-xs text-red-400 mt-1.5 ml-8">{{ $error }}</p>
    @endif
</div>
