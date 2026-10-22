{{--
    Área de upload de arquivo por clique ou por arrastar-e-soltar, que
    repassa os arquivos pro input nativo escondido. Usado em Fotos (imagens)
    e Documentos (qualquer arquivo) — por isso o texto de apoio dentro da
    caixa vem do prop $hint em vez de fixo, cada tela passa o seu (sem
    hint nenhum por padrão — um texto sobre tipo/tamanho de arquivo só faz
    sentido quando a tela que chama sabe de fato qual é a regra). Mostra o
    nome do arquivo escolhido (clique ou arrastar) em vez de ficar só com o
    texto genérico — sem isso, arquivos sem prévia visual (ex. .zip) não
    davam nenhuma confirmação de que algo foi selecionado.
--}}
@props([
    'label' => null,
    'error' => null,
    'hint' => null,
    'accept' => '*',
    'multiple' => false,
    'disabled' => false,
    'compact' => false,
])

@php
    $modelName = $attributes->wire('model')->value();
@endphp

<div
    class="space-y-1.5"
    x-data="{ files: [], dragging: false }"
    x-on:livewire-upload-error.window="
        if (! @js($modelName) || $event.detail.property === @js($modelName)) {
            window.dispatchEvent(new CustomEvent('toast', {
                detail: {
                    title: 'Envio de arquivo',
                    message: 'Não foi possível enviar o arquivo — ele pode ser grande demais para o servidor aceitar.',
                    variant: 'danger',
                },
            }));
        }
    "
>
    @if($label)
        <label class="block text-sm font-medium" style="color: var(--text-secondary);">
            {{ $label }}
        </label>
    @endif

    <div
        @if (! $disabled)
            @dragover.prevent="dragging = true"
            @dragleave="dragging = false"
            @drop.prevent="
                dragging = false;
                files = [...$event.dataTransfer.files];
                $refs.fileInput.files = $event.dataTransfer.files;
                $refs.fileInput.dispatchEvent(new Event('change'));
            "
            @click="$refs.fileInput.click()"
        @endif
        :class="dragging ? 'border-emerald-500/50' : ''"
        :style="dragging ? 'background-color: color-mix(in oklab, var(--color-emerald-500) 5%, transparent);' : ''"
        class="relative border-2 border-dashed rounded-xl text-center transition-colors duration-200 {{ $compact ? 'p-3' : 'p-8' }} {{ $disabled ? 'cursor-not-allowed opacity-50' : 'cursor-pointer' }}"
        style="border-color: var(--border-color);"
    >
        <input
            x-ref="fileInput"
            type="file"
            accept="{{ $accept }}"
            @change="files = [...$refs.fileInput.files]"
            @if($multiple) multiple @endif
            @if($disabled) disabled @endif
            {{ $attributes->merge(['class' => 'hidden']) }}
        />

        @if ($compact)
            <div class="flex items-center justify-center gap-2">
                <template x-if="files.length === 0">
                    <div class="flex items-center gap-2">
                        <x-ui.icon name="arrow-up-tray" class="w-4 h-4 shrink-0" style="color: var(--text-secondary);" />
                        <p class="text-xs" style="color: var(--text-primary);">
                            <span class="text-emerald-400 font-semibold">Enviar</span> ou arrastar
                            @if ($hint)
                                <span style="color: var(--text-muted);">· {{ $hint }}</span>
                            @endif
                        </p>
                    </div>
                </template>
                <template x-if="files.length > 0">
                    <p class="text-xs font-semibold truncate flex items-center gap-1.5" style="color: var(--text-primary);">
                        <i class="fa-solid fa-circle-check shrink-0" style="color: #059669;"></i>
                        <span x-text="files[0]?.name" class="truncate"></span>
                    </p>
                </template>
            </div>
        @else
            <template x-if="files.length === 0">
                <div class="space-y-3">
                    <div class="inline-flex items-center justify-center w-12 h-12 rounded-xl" style="background-color: var(--bg-elevated);">
                        <x-ui.icon name="arrow-up-tray" class="w-6 h-6" style="color: var(--text-secondary);" />
                    </div>
                    <div>
                        <p class="text-sm" style="color: var(--text-primary);">
                            <span class="text-emerald-400 font-semibold">Clique para enviar</span>
                            ou arraste e solte
                        </p>
                        @if ($hint)
                            <p class="text-xs mt-1" style="color: var(--text-muted);">{{ $hint }}</p>
                        @endif
                    </div>
                </div>
            </template>
            <template x-if="files.length > 0">
                <div class="space-y-2">
                    <div class="inline-flex items-center justify-center w-12 h-12 rounded-xl" style="background-color: color-mix(in srgb, #059669 15%, transparent);">
                        <i class="fa-solid fa-circle-check text-xl" style="color: #059669;"></i>
                    </div>
                    <p class="text-sm font-semibold px-4 truncate" style="color: var(--text-primary);" x-text="files[0]?.name"></p>
                </div>
            </template>
        @endif
    </div>

    <x-ui.upload-progress :target="$modelName" class="mt-2" />

    @if($error)
        <p class="text-xs text-red-400 mt-1.5">{{ $error }}</p>
    @endif
</div>
