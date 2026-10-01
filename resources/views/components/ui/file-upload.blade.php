@props([
    'label' => null,
    'error' => null,
    'hint' => null,
    'accept' => '*',
    'multiple' => false,
])

<div class="space-y-1.5" x-data="{ files: [], dragging: false }">
    @if($label)
        <label class="block text-sm font-medium" style="color: var(--text-secondary);">
            {{ $label }}
        </label>
    @endif

    <div
        @dragover.prevent="dragging = true"
        @dragleave="dragging = false"
        @drop.prevent="
            dragging = false;
            files = [...$event.dataTransfer.files];
            $refs.fileInput.files = $event.dataTransfer.files;
            $refs.fileInput.dispatchEvent(new Event('change'));
        "
        :class="dragging ? 'border-emerald-500/50' : ''"
        :style="dragging ? 'background-color: color-mix(in oklab, var(--color-emerald-500) 5%, transparent);' : ''"
        class="relative border-2 border-dashed rounded-xl p-8 text-center transition-colors duration-200 cursor-pointer"
        style="border-color: var(--border-color);"
        @click="$refs.fileInput.click()"
    >
        <input
            x-ref="fileInput"
            type="file"
            accept="{{ $accept }}"
            @if($multiple) multiple @endif
            {{ $attributes->merge(['class' => 'hidden']) }}
        />

        <div class="space-y-3">
            <div class="inline-flex items-center justify-center w-12 h-12 rounded-xl" style="background-color: var(--bg-elevated);">
                <x-ui.icon name="arrow-up-tray" class="w-6 h-6" style="color: var(--text-secondary);" />
            </div>
            <div>
                <p class="text-sm" style="color: var(--text-primary);">
                    <span class="text-emerald-400 font-semibold">Clique para enviar</span>
                    ou arraste e solte
                </p>
                <p class="text-xs mt-1" style="color: var(--text-muted);">SVG, PNG, JPG ou GIF (MAX. 800x400px)</p>
            </div>
        </div>
    </div>

    @if($error)
        <p class="text-xs text-red-400 mt-1.5">{{ $error }}</p>
    @elseif($hint)
        <p class="text-xs mt-1.5" style="color: var(--text-muted);">{{ $hint }}</p>
    @endif
</div>
