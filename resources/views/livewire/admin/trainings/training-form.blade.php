{{--
    Formulário de capacitação/curso (admin), aberto em modal.
    Cadastra/edita título, descrição, link externo do curso, carga
    horária, data de início e se está publicado para os servidores.
--}}
<form wire:submit="save" class="space-y-4">
    <x-ui.input wire:model="title" label="Título" />

    <div class="space-y-1.5">
        <label class="block text-sm font-medium" style="color: var(--text-secondary);">Descrição</label>
        <textarea wire:model="description" rows="3" class="w-full rounded-xl focus:outline-none focus:ring-2 focus:ring-(--focus-ring)/40 focus:border-(--focus-ring) transition-all duration-200 px-4 py-2.5 text-sm" style="background-color: var(--bg-input); border: 1px solid var(--border-color); color: var(--text-primary);"></textarea>
    </div>

    <x-ui.input wire:model="externalUrl" type="url" label="Link do curso" hint="Onde o conteúdo (vídeos, material) está hospedado." />

    <div class="grid grid-cols-2 gap-4">
        <x-ui.input wire:model="workloadHours" type="number" label="Carga horária (h)" />
        <x-ui.input wire:model="startsAt" type="date" label="Data de início" />
    </div>

    <x-ui.button type="submit">Salvar</x-ui.button>
</form>
