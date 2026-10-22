{{--
    Tela de gestão de Links de Serviços (admin).
    Lista os serviços exibidos no site, com reordenação por
    arrastar-e-soltar, e permite criar, editar, ativar/desativar e
    excluir links.
--}}
<div class="space-y-4">
    <!-- Cabeçalho da Página -->
    <x-admin.page-header
        icon="sliders"
        title="Serviços"
        subtitle="Gerencie os links de serviços exibidos no site."
        :breadcrumbs="[['label' => 'Painel', 'href' => route('dashboard')], ['label' => 'Serviços']]"
    >
        <x-slot:actions>
            @if (count($selected) > 0)
                <span class="text-xs font-semibold" style="color: var(--text-muted);">{{ count($selected) }} selecionado(s)</span>
                @can('services.update')
                    <x-ui.button type="button" wire:click="bulkActivate" variant="secondary" size="sm">
                        <i class="fa-solid fa-check mr-1"></i> Ativar
                    </x-ui.button>
                    <x-ui.button type="button" wire:click="bulkDeactivate" variant="secondary" size="sm">
                        <i class="fa-solid fa-ban mr-1"></i> Desativar
                    </x-ui.button>
                @endcan
                @can('services.delete')
                    <x-ui.button type="button" @click="confirmAction('Confirma a exclusão de {{ count($selected) }} serviço(s) selecionado(s)?', () => $wire.bulkDelete())" variant="danger" size="sm">
                        <i class="fa-solid fa-trash mr-1"></i> Excluir
                    </x-ui.button>
                @endcan
                <x-ui.button type="button" wire:click="deselectAll" variant="ghost" size="sm">Cancelar</x-ui.button>
            @else
                <x-ui.button wire:click="closeForm" x-on:click="window.dispatchEvent(new CustomEvent('open-modal', { detail: 'service-link-form' }))">
                    Novo serviço
                </x-ui.button>
            @endif
        </x-slot:actions>
    </x-admin.page-header>

    <!-- Estatísticas: um cartão só, ocupando a largura toda, com cada
         número dividido por uma linha fina — em vez de 3 caixas soltas com
         um vão vazio depois delas. Sem busca aqui: a lista é arrastável
         (reorder), então precisa ficar sempre completa na tela — filtrar
         quebraria o "reorder", que manda e valida a lista inteira a cada
         arrasto. -->
    <div class="ui-card flex items-stretch divide-x divide-(--border-color)">
        <div class="flex items-center gap-3 px-5 py-4 flex-1">
            <span class="w-10 h-10 rounded-lg flex items-center justify-center shrink-0" style="background-color: color-mix(in srgb, #2563EB 14%, transparent);">
                <i class="fa-solid fa-link text-sm" style="color: #2563EB;"></i>
            </span>
            <div class="whitespace-nowrap">
                <p class="text-[10px] font-bold uppercase tracking-wide leading-none" style="color: var(--text-muted);">Total</p>
                <p class="text-xl font-black leading-tight" style="color: var(--text-primary);">{{ $totalLinks }}</p>
            </div>
        </div>

        <div class="flex items-center gap-3 px-5 py-4 flex-1">
            <span class="w-10 h-10 rounded-lg flex items-center justify-center shrink-0" style="background-color: color-mix(in srgb, #059669 14%, transparent);">
                <i class="fa-solid fa-check text-sm" style="color: #059669;"></i>
            </span>
            <div class="whitespace-nowrap">
                <p class="text-[10px] font-bold uppercase tracking-wide leading-none" style="color: var(--text-muted);">Ativos</p>
                <p class="text-xl font-black leading-tight" style="color: var(--text-primary);">{{ $activeLinks }}</p>
            </div>
        </div>

        <div class="flex items-center gap-3 px-5 py-4 flex-1">
            <span class="w-10 h-10 rounded-lg flex items-center justify-center shrink-0" style="background-color: color-mix(in srgb, #DC2626 14%, transparent);">
                <i class="fa-solid fa-ban text-sm" style="color: #DC2626;"></i>
            </span>
            <div class="whitespace-nowrap">
                <p class="text-[10px] font-bold uppercase tracking-wide leading-none" style="color: var(--text-muted);">Inativos</p>
                <p class="text-xl font-black leading-tight" style="color: var(--text-primary);">{{ $inactiveLinks }}</p>
            </div>
        </div>
    </div>

    <!-- Tabela de Serviços (Reordenável) -->
    <x-ui.data-table sortable sortMethod="reorder" selectable>
        <x-slot:header>
            <th class="px-4 py-3 w-8"><x-ui.table-checkbox header wire:click="toggleSelectAllOnPage" :checked="$allSelectedOnPage" /></th>
            <th class="px-4 py-3 w-8"></th>
            <th class="px-6 py-3"><i class="fa-solid fa-gear mr-1 opacity-60"></i> Serviço</th>
            <th class="px-6 py-3"><i class="fa-solid fa-link mr-1 opacity-60"></i> URL</th>
            <th class="px-6 py-3"><i class="fa-solid fa-circle-check mr-1 opacity-60"></i> Status</th>
            <th class="px-6 py-3"><i class="fa-solid fa-bolt mr-1 opacity-60"></i> Ações</th>
        </x-slot:header>

        @foreach ($links as $link)
            <tr
                wire:key="service-link-{{ $link->id }}"
                data-sort-id="{{ $link->id }}"
                draggable="false"
                x-on:dragstart="start($event)"
                x-on:dragend="end($event)"
                x-on:dragover.prevent
                x-on:drop="drop($event)"
            >
                <td class="px-4 py-3"><x-ui.table-checkbox wire:model.live="selected" value="{{ $link->id }}" /></td>
                <td class="px-4 py-3">
                    <x-ui.drag-handle />
                </td>
                <td class="px-6 py-3">
                    <div class="flex items-center gap-2.5">
                        <span class="w-9 h-9 rounded-full flex items-center justify-center shrink-0" style="background-color: var(--admin-primary-light);">
                            <i class="fa-solid fa-{{ $link->icon }}" style="color: var(--admin-primary);"></i>
                        </span>
                        <span class="font-semibold" style="color: var(--text-primary);">{{ $link->title }}</span>
                    </div>
                </td>
                <td class="px-6 py-3 truncate max-w-xs" style="color: var(--text-secondary);">{{ $link->url }}</td>
                <td class="px-6 py-3"><x-ui.badge :variant="$link->is_active ? 'success' : 'default'">{{ $link->is_active ? 'Ativo' : 'Inativo' }}</x-ui.badge></td>
                <td class="px-6 py-3 space-x-2 whitespace-nowrap">
                    <x-ui.tooltip text="Editar">
                        <button wire:click="edit({{ $link->id }})" x-on:click="window.dispatchEvent(new CustomEvent('open-modal', { detail: 'service-link-form' }))" class="w-9 h-9 inline-flex items-center justify-center rounded transition hover:brightness-95" style="background-color: rgba(37, 99, 235, 0.1); color: #2563EB;">
                            <i class="fa-solid fa-pen"></i>
                        </button>
                    </x-ui.tooltip>
                    <x-ui.tooltip :text="$link->is_active ? 'Desativar' : 'Ativar'">
                        <button wire:click="toggleActive({{ $link->id }})" class="w-9 h-9 inline-flex items-center justify-center rounded transition hover:brightness-95" style="background-color: rgba(217, 119, 6, 0.1); color: #D97706;">
                            <i class="fa-solid fa-{{ $link->is_active ? 'toggle-off' : 'toggle-on' }}"></i>
                        </button>
                    </x-ui.tooltip>
                    <x-ui.tooltip text="Excluir">
                        <button @click="confirmAction('Confirma a exclusão deste serviço?', () => $wire.delete({{ $link->id }}))" class="w-9 h-9 inline-flex items-center justify-center rounded transition hover:brightness-95" style="background-color: rgba(220, 38, 38, 0.1); color: #DC2626;">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </x-ui.tooltip>
                </td>
            </tr>
        @endforeach
    </x-ui.data-table>

    <!-- Modal: Formulário de Serviço -->
    <x-ui.modal name="service-link-form" maxWidth="lg">
        <x-slot:header>
            <div class="flex items-center gap-3">
                <span class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0" style="background: linear-gradient(135deg, var(--admin-primary, var(--brand-primary, #0C4A34)), color-mix(in srgb, var(--admin-primary, var(--brand-primary, #0C4A34)) 70%, black));">
                    <i class="fa-solid fa-link text-white"></i>
                </span>
                <div>
                    <span class="text-[10px] font-bold uppercase tracking-wide" style="color: var(--admin-primary, var(--brand-primary, #0C4A34));">Serviços</span>
                    <p class="font-black text-base leading-snug" style="color: var(--text-primary);">{{ $editingLink ? 'Editar serviço' : 'Novo serviço' }}</p>
                </div>
            </div>
        </x-slot:header>
        <livewire:admin.services.service-link-form :link="$editingLink" :key="'service-link-form-'.($editingLinkId ?? 'new')" />
    </x-ui.modal>
</div>
