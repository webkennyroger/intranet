<div class="max-w-7xl mx-auto space-y-4">
    <div class="flex items-center justify-between">
        <h2 class="text-xl font-bold text-slate-800">Usuários</h2>
        <div>
            <x-ui.button wire:click="closeForm" x-on:click="window.dispatchEvent(new CustomEvent('open-modal', { detail: 'user-form' }))">
                Novo usuário
            </x-ui.button>
        </div>
    </div>

    @error('delete') <div class="text-red-600 text-sm">{{ $message }}</div> @enderror
    @error('toggleBlock') <div class="text-red-600 text-sm">{{ $message }}</div> @enderror

    <x-ui.data-table>
        <x-slot:header>
            <th class="px-6 py-3">Nome</th>
            <th class="px-6 py-3">E-mail</th>
            <th class="px-6 py-3">Papel</th>
            <th class="px-6 py-3">Status</th>
            <th class="px-6 py-3">Ações</th>
        </x-slot:header>

        @foreach ($users as $user)
            <tr>
                <td class="px-6 py-3">{{ $user->name }}</td>
                <td class="px-6 py-3">{{ $user->email }}</td>
                <td class="px-6 py-3">{{ $user->getRoleNames()->first() ?? '—' }}</td>
                <td class="px-6 py-3">{{ $user->status->label() }}</td>
                <td class="px-6 py-3 space-x-2">
                    <button wire:click="edit({{ $user->id }})" x-on:click="window.dispatchEvent(new CustomEvent('open-modal', { detail: 'user-form' }))" class="text-sm text-emerald-700 hover:underline">
                        Editar
                    </button>
                    <button wire:click="toggleBlock({{ $user->id }})" class="text-sm text-amber-700 hover:underline">
                        {{ $user->isActive() ? 'Bloquear' : 'Desbloquear' }}
                    </button>
                    <button wire:click="delete({{ $user->id }})" wire:confirm="Confirma a exclusão deste usuário?" class="text-sm text-red-700 hover:underline">
                        Excluir
                    </button>
                </td>
            </tr>
        @endforeach

        <x-slot:footer>
            {{ $users->links() }}
        </x-slot:footer>
    </x-ui.data-table>

    <x-ui.modal name="user-form" maxWidth="lg">
        <x-slot:header>Usuário</x-slot:header>
        <livewire:admin.users.user-form :user="$editingUser" :key="'user-form-'.($editingUserId ?? 'new')" />
    </x-ui.modal>
</div>
