{{--
    Barra de progresso de envio de arquivo, pra qualquer upload via Livewire
    (WithFileUploads). "target" é o nome exato da propriedade wire:model do
    campo (ex.: "photo", "files", "customFields.0.file") — os eventos de
    upload do Livewire já dizem qual propriedade foi, então a barra só
    aparece/anima na vez desse campo específico, mesmo com vários campos de
    upload na mesma página. Já embutida no x-ui.file-upload; só precisa ser
    usada à parte em campo que não usa aquele componente (input nativo).
--}}
@props(['target'])

<div
    x-data="{ active: false, progress: 0 }"
    x-on:livewire-upload-start.window="if ($event.detail.property === @js($target)) { active = true; progress = 0; }"
    x-on:livewire-upload-progress.window="if (active) { progress = $event.detail.progress; }"
    x-on:livewire-upload-finish.window="if ($event.detail.property === @js($target)) { active = false; }"
    x-on:livewire-upload-cancel.window="if ($event.detail.property === @js($target)) { active = false; }"
    x-on:livewire-upload-error.window="if ($event.detail.property === @js($target)) { active = false; }"
    x-show="active"
    x-cloak
    {{ $attributes->merge(['class' => 'space-y-1']) }}
>
    <div class="flex items-center justify-between text-xs" style="color: var(--text-muted);">
        <span>Enviando...</span>
        <span x-text="progress + '%'"></span>
    </div>
    <div class="w-full h-1.5 rounded-full overflow-hidden" style="background-color: var(--bg-elevated);">
        <div class="h-full rounded-full transition-all duration-200" :style="`width: ${progress}%; background-color: var(--admin-primary, var(--brand-primary, #0C4A34));`"></div>
    </div>
</div>
