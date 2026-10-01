@props([
    'label' => null,
    'description' => null,
    'hint' => null,
    'error' => null,
    'checked' => false,
])

@php
    $help = $description ?? $hint;
    $hasError = filled($error);
    $boxBorder = $hasError ? 'border-red-500/50' : 'border-[var(--border-color)]';
@endphp

<div>
    <label class="inline-flex items-start gap-3 cursor-pointer select-none">
        <span class="relative inline-flex h-5 w-5 shrink-0 mt-0.5">
            <input
                type="checkbox"
                @checked($checked)
                {{ $attributes->merge(['class' => 'peer sr-only']) }}
            />

            <span
                class="absolute inset-0 rounded-md border bg-[var(--bg-input)] {{ $boxBorder }} transition-colors duration-150 peer-checked:bg-emerald-500 peer-checked:border-emerald-500 peer-focus-visible:ring-2 peer-focus-visible:ring-emerald-500/40"
                aria-hidden="true"
            ></span>

            <x-ui.icon
                name="check"
                class="absolute inset-0 m-auto w-3.5 h-3.5 text-white opacity-0 scale-75 transition-all duration-150 peer-checked:opacity-100 peer-checked:scale-100"
            />
        </span>

        @if($label || $slot->isNotEmpty())
            <span class="text-sm leading-5">
                <span class="block font-medium" style="color: var(--text-primary);">{{ $label ?? $slot }}</span>
                @if($help)
                    <span class="block mt-0.5" style="color: var(--text-muted);">{{ $help }}</span>
                @endif
            </span>
        @endif
    </label>

    @if($error)
        <p class="text-xs text-red-400 mt-1.5 ml-8">{{ $error }}</p>
    @endif
</div>
