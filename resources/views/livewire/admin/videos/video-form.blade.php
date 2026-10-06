{{--
    Formulário de vídeo (admin), aberto em modal.
    Cadastra/edita título, descrição, URL do vídeo (YouTube, Vimeo etc.)
    e se está publicado no site.
--}}
<form wire:submit="save" class="space-y-4">
    <x-ui.input wire:model="title" label="Título" />

    <div class="space-y-1.5">
        <label class="block text-sm font-medium" style="color: var(--text-secondary);">Descrição</label>
        <textarea wire:model="description" rows="3" class="w-full rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500/40 focus:border-emerald-500 transition-all duration-200 px-4 py-2.5 text-sm" style="background-color: var(--bg-input); border: 1px solid var(--border-color); color: var(--text-primary);"></textarea>
    </div>

    <x-ui.input wire:model="url" type="url" label="URL do vídeo" hint="Link do YouTube, Vimeo ou outro serviço." />

    <x-ui.checkbox wire:model="isPublished" label="Publicado" />

    <x-ui.button type="submit">Salvar</x-ui.button>
</form>
