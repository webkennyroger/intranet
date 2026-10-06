{{--
    Formulário de evento (admin), aberto em modal.
    Cadastra/edita título, descrição, local, datas de início/término e
    se o evento está publicado no site.
--}}
<form wire:submit="save" class="space-y-4">
    <x-ui.input wire:model="title" label="Título" />

    <div class="space-y-1.5">
        <label class="block text-sm font-medium" style="color: var(--text-secondary);">Descrição</label>
        <textarea wire:model="description" rows="3" class="w-full rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500/40 focus:border-emerald-500 transition-all duration-200 px-4 py-2.5 text-sm" style="background-color: var(--bg-input); border: 1px solid var(--border-color); color: var(--text-primary);"></textarea>
    </div>

    <x-ui.input wire:model="location" label="Local" />

    <div class="grid grid-cols-2 gap-4">
        <x-ui.input wire:model="startsAt" type="datetime-local" label="Início" />
        <x-ui.input wire:model="endsAt" type="datetime-local" label="Término" />
    </div>

    <x-ui.checkbox wire:model="isPublished" label="Publicado" />

    <x-ui.button type="submit">Salvar</x-ui.button>
</form>
