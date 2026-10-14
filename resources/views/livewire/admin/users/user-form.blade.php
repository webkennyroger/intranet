{{--
    Formulário de usuário (admin), aberto em modal.
    Cadastra/edita dados de acesso (nome, usuário, e-mail, senha) e
    perfil (papel, departamento, data de nascimento usada na página
    de Aniversariantes).
--}}
<form wire:submit="save" class="space-y-4">
    <x-ui.input wire:model="name" label="Nome" />
    <x-ui.input wire:model="username" label="Usuário" />
    <x-ui.input wire:model="email" type="email" label="E-mail" />
    <x-ui.input wire:model="password" type="password" label="Senha" :hint="$user ? 'Deixe em branco para manter a senha atual.' : null" />

    <x-ui.select wire:model.live="role" label="Papel">
        @foreach ($roles as $r)
            <option value="{{ $r->name }}">{{ $r->name }}</option>
        @endforeach
    </x-ui.select>

    <x-ui.input wire:model="department" label="Departamento/Setor" />

    <x-ui.input wire:model="birthDate" type="date" label="Data de nascimento" hint="Usada na página de Aniversariantes." />

    <x-ui.button type="submit">Salvar</x-ui.button>
</form>
