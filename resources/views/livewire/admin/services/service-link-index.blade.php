<div class="max-w-7xl mx-auto space-y-4">
    <div class="flex items-center justify-between">
        <h2 class="text-xl font-bold text-slate-800">Serviços</h2>
        <div>
            <x-ui.button wire:click="closeForm" x-on:click="window.dispatchEvent(new CustomEvent('open-modal', { detail: 'service-link-form' }))">
                Novo serviço
            </x-ui.button>
        </div>
    </div>

    <x-ui.data-table>
        <x-slot:header>
            <th class="px-6 py-3">Ícone</th>
            <th class="px-6 py-3">Título</th>
            <th class="px-6 py-3">URL</th>
            <th class="px-6 py-3">Status</th>
            <th class="px-6 py-3">Ações</th>
        </x-slot:header>

        @foreach ($links as $link)
            <tr>
                <td class="px-6 py-3"><i class="fa-solid fa-{{ $link->icon }} text-emerald-800"></i></td>
                <td class="px-6 py-3">{{ $link->title }}</td>
                <td class="px-6 py-3 text-slate-500 truncate max-w-xs">{{ $link->url }}</td>
                <td class="px-6 py-3">{{ $link->is_active ? 'Ativo' : 'Inativo' }}</td>
                <td class="px-6 py-3 space-x-2 whitespace-nowrap">
                    <button wire:click="moveUp({{ $link->id }})" class="text-sm text-slate-500 hover:underline">
                        <i class="fa-solid fa-arrow-up"></i>
                    </button>
                    <button wire:click="moveDown({{ $link->id }})" class="text-sm text-slate-500 hover:underline">
                        <i class="fa-solid fa-arrow-down"></i>
                    </button>
                    <button wire:click="edit({{ $link->id }})" x-on:click="window.dispatchEvent(new CustomEvent('open-modal', { detail: 'service-link-form' }))" class="text-sm text-emerald-700 hover:underline">
                        Editar
                    </button>
                    <button wire:click="toggleActive({{ $link->id }})" class="text-sm text-amber-700 hover:underline">
                        {{ $link->is_active ? 'Desativar' : 'Ativar' }}
                    </button>
                    <button wire:click="delete({{ $link->id }})" wire:confirm="Confirma a exclusão deste serviço?" class="text-sm text-red-700 hover:underline">
                        Excluir
                    </button>
                </td>
            </tr>
        @endforeach
    </x-ui.data-table>

    <x-ui.modal name="service-link-form" maxWidth="lg">
        <x-slot:header>Serviço</x-slot:header>
        <livewire:admin.services.service-link-form :link="$editingLink" :key="'service-link-form-'.($editingLinkId ?? 'new')" />
    </x-ui.modal>
</div>
