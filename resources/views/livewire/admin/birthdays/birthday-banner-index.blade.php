{{--
    Tela de gestão de Banners de Aniversariantes (admin).
    Lista os 12 meses do ano (um banner no máximo por mês) e permite
    enviar, editar, publicar/despublicar e excluir a arte de cada um.
--}}
<div class="space-y-4">
    <!-- Cabeçalho da Página -->
    <x-admin.page-header
        icon="cake-candles"
        title="Banners de Aniversariantes"
        subtitle="Cadastre a arte comemorativa exibida na página de Aniversariantes de cada mês."
        :breadcrumbs="[['label' => 'Painel', 'href' => route('dashboard')], ['label' => 'Banners de Aniversariantes']]"
    />

    <!-- Total: compacto, em vez de um grid de cards de dashboard inteiro. -->
    <div class="ui-card px-4 py-2.5 flex items-center gap-3 w-fit" style="border-top: 2px solid #2563EB;">
        <span class="w-9 h-9 rounded-lg flex items-center justify-center shrink-0" style="background-color: color-mix(in srgb, #2563EB 14%, transparent);">
            <i class="fa-solid fa-cake-candles text-sm" style="color: #2563EB;"></i>
        </span>
        <div class="whitespace-nowrap">
            <p class="text-[10px] font-bold uppercase tracking-wide leading-none" style="color: var(--text-muted);">Meses com banner</p>
            <p class="text-lg font-black leading-tight" style="color: var(--text-primary);">{{ $totalBanners }} / 12</p>
        </div>
    </div>

    <!-- Tabela: sempre os 12 meses, cada um com sua própria imagem (ou não). -->
    <x-ui.data-table>
        <x-slot:header>
            <th class="px-6 py-3"><i class="fa-solid fa-image mr-1 opacity-60"></i> Imagem</th>
            <th class="px-6 py-3"><i class="fa-solid fa-cake-candles mr-1 opacity-60"></i> Mês</th>
            <th class="px-6 py-3"><i class="fa-solid fa-circle-check mr-1 opacity-60"></i> Status</th>
            <th class="px-6 py-3"><i class="fa-solid fa-bolt mr-1 opacity-60"></i> Ações</th>
        </x-slot:header>

        @foreach ($months as $row)
            <tr wire:key="banner-month-{{ $row['month'] }}">
                <td class="px-6 py-3">
                    @if ($row['banner'])
                        <img src="{{ $row['banner']->imageUrl() }}" alt="{{ $row['name'] }}" class="w-16 h-10 object-cover rounded-md" />
                    @else
                        <span class="w-16 h-10 inline-flex items-center justify-center rounded-md" style="background-color: var(--bg-elevated);">
                            <i class="fa-solid fa-image text-sm" style="color: var(--text-muted);"></i>
                        </span>
                    @endif
                </td>
                <td class="px-6 py-3 font-medium" style="color: var(--text-primary);">{{ $row['name'] }}</td>
                <td class="px-6 py-3">
                    @if ($row['banner'])
                        <x-ui.badge :variant="$row['banner']->is_published ? 'success' : 'warning'">{{ $row['banner']->is_published ? 'Publicado' : 'Rascunho' }}</x-ui.badge>
                    @else
                        <span class="text-xs" style="color: var(--text-muted);">Sem banner</span>
                    @endif
                </td>
                <td class="px-6 py-3 space-x-2 whitespace-nowrap">
                    @if ($row['banner'])
                        <x-ui.tooltip text="Editar">
                            <button wire:click="edit({{ $row['banner']->id }})" x-on:click="window.dispatchEvent(new CustomEvent('open-modal', { detail: 'birthday-banner-form' }))" class="w-9 h-9 inline-flex items-center justify-center rounded transition hover:brightness-95" style="background-color: rgba(37, 99, 235, 0.1); color: #2563EB;">
                                <i class="fa-solid fa-pen"></i>
                            </button>
                        </x-ui.tooltip>
                        @can('birthday-banners.update')
                            <x-ui.tooltip :text="$row['banner']->is_published ? 'Despublicar' : 'Publicar'">
                                <button wire:click="togglePublish({{ $row['banner']->id }})" class="w-9 h-9 inline-flex items-center justify-center rounded transition hover:brightness-95" style="background-color: rgba(217, 119, 6, 0.1); color: #D97706;">
                                    <i class="fa-solid fa-{{ $row['banner']->is_published ? 'toggle-off' : 'toggle-on' }}"></i>
                                </button>
                            </x-ui.tooltip>
                        @endcan
                        @can('birthday-banners.delete')
                            <x-ui.tooltip text="Excluir">
                                <button @click="confirmAction('Confirma a exclusão deste banner?', () => $wire.delete({{ $row['banner']->id }}))" class="w-9 h-9 inline-flex items-center justify-center rounded transition hover:brightness-95" style="background-color: rgba(220, 38, 38, 0.1); color: #DC2626;">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </x-ui.tooltip>
                        @endcan
                    @else
                        @can('birthday-banners.create')
                            <x-ui.button type="button" wire:click="createForMonth({{ $row['month'] }})" x-on:click="window.dispatchEvent(new CustomEvent('open-modal', { detail: 'birthday-banner-form' }))" variant="secondary" size="sm">
                                <i class="fa-solid fa-upload mr-1"></i> Enviar banner
                            </x-ui.button>
                        @endcan
                    @endif
                </td>
            </tr>
        @endforeach
    </x-ui.data-table>

    <!-- Modal: Formulário de Banner -->
    <x-ui.modal name="birthday-banner-form" maxWidth="lg">
        <x-slot:header>
            <div class="flex items-center gap-3">
                <span class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0" style="background: linear-gradient(135deg, var(--admin-primary, var(--brand-primary, #0C4A34)), color-mix(in srgb, var(--admin-primary, var(--brand-primary, #0C4A34)) 70%, black));">
                    <i class="fa-solid fa-cake-candles text-white"></i>
                </span>
                <div>
                    <span class="text-[10px] font-bold uppercase tracking-wide" style="color: var(--admin-primary, var(--brand-primary, #0C4A34));">Aniversariantes</span>
                    <p class="font-black text-base leading-snug" style="color: var(--text-primary);">{{ $editingBanner ? 'Editar banner' : 'Novo banner' }}</p>
                </div>
            </div>
        </x-slot:header>
        <livewire:admin.birthdays.birthday-banner-form :banner="$editingBanner" :prefillMonth="$prefillMonth" :key="'birthday-banner-form-'.($editingBannerId ?? $prefillMonth ?? 'new')" />
    </x-ui.modal>
</div>
