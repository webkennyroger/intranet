@props([
    'label' => null,
    'placeholder' => null,
    'maxLength' => null,
    'rows' => 2,
    'maxRows' => 8,
])

@php
    $maxLengthAttr = $maxLength ? (int) $maxLength : null;
    $maxHeightPx = ((int) $maxRows * 24) + 24;
@endphp

<div
    x-data="{
        length: 0,
        maxLength: {{ $maxLengthAttr ?? 'null' }},
        resize(el) {
            el.style.height = 'auto';
            el.style.height = Math.min(el.scrollHeight, {{ $maxHeightPx }}) + 'px';
        },
    }"
    x-init="length = $refs.composerInput.value.length; resize($refs.composerInput)"
    class="space-y-1.5"
>
    @if($label)
        <label class="block text-sm font-medium" style="color: var(--text-secondary);">
            {{ $label }}
        </label>
    @endif

    <div class="rounded-xl overflow-hidden" style="background-color: var(--bg-input); border: 1px solid var(--border-color);">
        <textarea
            x-ref="composerInput"
            rows="{{ $rows }}"
            @if($maxLengthAttr) maxlength="{{ $maxLengthAttr }}" @endif
            @if($placeholder) placeholder="{{ $placeholder }}" @endif
            x-on:input="length = $event.target.value.length; resize($event.target)"
            {{ $attributes->merge([
                'class' => 'w-full resize-none border-0 bg-transparent focus:outline-none focus:ring-0 px-4 py-3 text-sm',
            ]) }}
            style="color: var(--text-primary);"
        ></textarea>

        <div class="flex items-center justify-between gap-3 px-3 py-2 border-t" style="border-color: var(--border-color);">
            <div class="flex items-center gap-1.5">
                {{ $actions ?? '' }}
            </div>

            @if($maxLengthAttr)
                <span
                    class="text-xs tabular-nums flex-shrink-0"
                    :style="length > maxLength ? 'color: #f87171' : 'color: var(--text-muted)'"
                >
                    <span x-text="length"></span>/{{ $maxLengthAttr }}
                </span>
            @endif
        </div>
    </div>
</div>
