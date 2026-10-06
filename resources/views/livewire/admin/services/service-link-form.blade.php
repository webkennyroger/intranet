{{--
    Formulário de link de serviço (admin), aberto em modal.
    Cadastra/edita título, ícone (Font Awesome), URL de destino e se
    o serviço está ativo (visível no site).
--}}
<form wire:submit="save" class="space-y-4">
    <x-ui.input wire:model="title" label="Título" />
    <x-ui.input wire:model="icon" label="Ícone (Font Awesome)" hint="Nome do ícone sem o prefixo, ex: book-open" />
    <x-ui.input wire:model="url" type="url" label="URL" />
    <x-ui.checkbox wire:model="isActive" label="Ativo" />

    <x-ui.button type="submit">Salvar</x-ui.button>
</form>
