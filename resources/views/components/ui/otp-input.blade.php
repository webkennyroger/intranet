@props([
    'length' => 6,
    'label' => null,
    'hint' => null,
    'error' => null,
    'value' => null,
    'numeric' => true,
])

@php
    $modelName = $attributes->wire('model')->value();
    $len = max(1, (int) $length);
    $initial = $value ?? '';
@endphp

<div
    x-data="{
        length: {{ $len }},
        digits: Array.from({ length: {{ $len }} }, () => ''),
        code: @if($modelName) $wire.entangle('{{ $modelName }}') @else '{{ $initial }}' @endif,
        init() {
            if (this.code) {
                this.code.split('').slice(0, this.length).forEach((c, i) => { this.digits[i] = c; });
            }
            this.$watch('digits', () => { this.code = this.digits.join(''); });
        },
        onInput(index, event) {
            this.digits[index] = event.target.value.slice(-1);
            if (this.digits[index] && index < this.length - 1) {
                event.target.nextElementSibling?.focus();
            }
        },
        onKeydown(index, event) {
            if (event.key === 'Backspace' && this.digits[index] === '' && index > 0) {
                event.target.previousElementSibling?.focus();
                this.digits[index - 1] = '';
            }
        },
        onPaste(index, event) {
            event.preventDefault();
            const pasted = ((event.clipboardData || window.clipboardData).getData('text') || '').replace(/\s+/g, '');
            const chars = pasted.split('').slice(0, this.length - index);
            let target = event.target;
            chars.forEach((c, offset) => {
                this.digits[index + offset] = c;
                if (offset > 0) target = target.nextElementSibling ?? target;
            });
            this.$nextTick(() => target?.focus());
        },
    }"
    class="space-y-1.5"
>
    @if($label)
        <label class="block text-sm font-medium" style="color: var(--text-secondary);">
            {{ $label }}
        </label>
    @endif

    <div class="flex items-center gap-2">
        <template x-for="(digit, index) in digits" :key="index">
            <input
                type="text"
                @if($numeric) inputmode="numeric" pattern="[0-9]*" @endif
                autocomplete="one-time-code"
                maxlength="1"
                :value="digit"
                @input="onInput(index, $event)"
                @keydown="onKeydown(index, $event)"
                @paste="onPaste(index, $event)"
                @focus="$event.target.select()"
                {{ $attributes->whereDoesntStartWith('wire:model')->merge([
                    'class' => 'w-11 h-12 text-center text-lg font-semibold rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500/40 focus:border-emerald-500 transition-all duration-200 ' .
                        ($error ? 'border-red-500/50 focus:ring-red-500/40' : ''),
                ]) }}
                style="background-color: var(--bg-input); border: 1px solid {{ $error ? 'rgba(239, 68, 68, 0.5)' : 'var(--border-color)' }}; color: var(--text-primary);"
            />
        </template>
    </div>

    @if($error)
        <p class="text-xs text-red-400 mt-1.5">{{ $error }}</p>
    @elseif($hint)
        <p class="text-xs mt-1.5" style="color: var(--text-muted);">{{ $hint }}</p>
    @endif
</div>
