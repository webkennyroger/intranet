{{--
    Formulário de link de serviço (admin), aberto em modal.
    Cadastra/edita título, ícone (Font Awesome), URL de destino e se
    o serviço está ativo (visível no site).
--}}
<form wire:submit="save" class="space-y-4">
    <x-ui.input wire:model="title" label="Título" />
    <x-ui.autocomplete wire:model="icon" :options="\App\Services\IconCatalog::suggestions()" label="Ícone (Font Awesome)" hint="Nome do ícone sem o prefixo, ex: book-open" />
    <x-ui.input wire:model="url" type="url" label="URL" />
    <x-ui.switch wire:model="isActive" label="Ativo" description="Exibe este serviço no site." />

    <x-ui.button type="submit" class="w-full justify-center">
        <i class="fa-solid fa-floppy-disk mr-1.5"></i> Salvar
    </x-ui.button>
</form>
