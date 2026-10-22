{{--
    Formulário de categoria/tipo rápido de evento (nome + cor), aberto em
    modal a partir da barra lateral do calendário.
--}}
<form wire:submit="save" class="space-y-4">
    <x-ui.input wire:model="name" label="Nome" placeholder="Ex.: Reunião" />

    <x-ui.color-picker wire:model="color" label="Cor" :error="$errors->first('color')" />

    <x-ui.button type="submit" class="w-full justify-center">Salvar</x-ui.button>
</form>
