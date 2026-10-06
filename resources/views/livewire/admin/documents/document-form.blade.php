{{--
    Formulário de documento (admin), aberto em modal.
    Cadastra/edita título, descrição, arquivo (upload) e se o documento
    está publicado para os servidores.
--}}
<form wire:submit="save" class="space-y-4">
    <x-ui.input wire:model="title" label="Título" />

    <div class="space-y-1.5">
        <label class="block text-sm font-medium" style="color: var(--text-secondary);">Descrição</label>
        <textarea wire:model="description" rows="3" class="w-full rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500/40 focus:border-emerald-500 transition-all duration-200 px-4 py-2.5 text-sm" style="background-color: var(--bg-input); border: 1px solid var(--border-color); color: var(--text-primary);"></textarea>
    </div>

    <!-- Upload de Arquivo -->
    <div class="space-y-1.5">
        <label class="block text-sm font-medium" style="color: var(--text-secondary);">Arquivo</label>
        @if ($document?->file_path)
            <p class="text-xs mb-1" style="color: var(--text-muted);">Arquivo atual: {{ basename($document->file_path) }}. Envie um novo para substituir.</p>
        @endif
        <input type="file" wire:model="file" class="block w-full text-sm" style="color: var(--text-secondary);">
        <div wire:loading wire:target="file" class="text-xs" style="color: var(--text-muted);">Enviando...</div>
        @error('file') <p class="text-xs text-red-400">{{ $message }}</p> @enderror
    </div>

    <x-ui.checkbox wire:model="isPublished" label="Publicado" />

    <x-ui.button type="submit">Salvar</x-ui.button>
</form>
