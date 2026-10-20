{{--
    Formulário de processo SEI do Conselho Superior (admin), aberto em
    modal. O link externo é opcional — sem ele, o número do processo
    aparece na listagem sem abrir nada ao clicar.
--}}
<form wire:submit="save" class="space-y-4">
    <x-ui.input wire:model="seiNumber" label="Número do processo SEI" placeholder="Ex: 2025.0.000019186-8" />
    <x-ui.input wire:model="subject" label="Assunto" />
    <x-ui.input wire:model="assignedTo" label="Relator/Atribuído" />
    <x-ui.input wire:model="interestedParty" label="Interessado" />
    <x-ui.input wire:model="externalUrl" type="url" label="Link do SEI (opcional)" hint="Se preenchido, o número do processo abre esse link numa nova aba." />

    <x-ui.button type="submit">Salvar</x-ui.button>
</form>
