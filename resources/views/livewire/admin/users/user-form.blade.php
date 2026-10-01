<form wire:submit="save" class="space-y-4">
    <x-ui.input wire:model="name" label="Nome" />
    <x-ui.input wire:model="username" label="Usuário" />
    <x-ui.input wire:model="email" type="email" label="E-mail" />
    <x-ui.input wire:model="password" type="password" label="Senha" :hint="$user ? 'Deixe em branco para manter a senha atual.' : null" />

    <x-ui.select wire:model.live="role" label="Papel">
        <option value="Super Administrador">Super Administrador</option>
        <option value="Administrador">Administrador</option>
        <option value="Gestor">Gestor</option>
        <option value="Editor">Editor</option>
    </x-ui.select>

    @if ($role === 'Gestor')
        <x-ui.input wire:model="department" label="Departamento/Setor" />
    @endif

    <x-ui.button type="submit">Salvar</x-ui.button>
</form>
