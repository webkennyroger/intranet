{{--
    Tela de gestão de Banners de Aniversariantes (admin).
    Lista os banners comemorativos cadastrados por mês e permite criar,
    editar, publicar/despublicar e excluir cada um.
--}}
<div class="space-y-4">
    <!-- Cabeçalho da Página -->
    <x-admin.page-header
        icon="cake-candles"
        title="Banners de Aniversariantes"
        subtitle="Cadastre a arte comemorativa exibida na página de Aniversariantes de cada mês."
        :breadcrumbs="[['label' => 'Painel', 'href' => route('dashboard')], ['label' => 'Banners de Aniversariantes']]"
    >
        <x-slot:actions>
            <x-ui.button wire:click="closeForm" x-on:click="window.dispatchEvent(new CustomEvent('open-modal', { detail: 'birthday-banner-form' }))">
                Novo banner
            </x-ui.button>
        </x-slot:actions>
    </x-admin.page-header>

    <!-- Estatísticas (Cards) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <x-admin.trend-stat-card label="Total de banners" :value="$totalBanners" :changePercent="$totalBannersTrend['changePercent']" :values="$totalBannersTrend['values']" icon="cake-candles" color="#2563EB" />
        <x-admin.trend-stat-card label="Publicados" :value="$publishedBanners" :changePercent="$publishedBannersTrend['changePercent']" :values="$publishedBannersTrend['values']" icon="check" color="#059669" />
    </div>

    <!-- Tabela de Banners -->
    @if ($banners->isEmpty())
        <x-ui.empty-state title="Nenhum banner cadastrado ainda." icon="cake-candles" />
    @else
        <x-ui.data-table>
            <x-slot:header>
                <th class="px-6 py-3"><i class="fa-solid fa-image mr-1 opacity-60"></i> Imagem</th>
                <th class="px-6 py-3"><i class="fa-solid fa-cake-candles mr-1 opacity-60"></i> Mês</th>
                <th class="px-6 py-3"><i class="fa-solid fa-circle-check mr-1 opacity-60"></i> Status</th>
                <th class="px-6 py-3"><i class="fa-solid fa-bolt mr-1 opacity-60"></i> Ações</th>
            </x-slot:header>

            @foreach ($banners as $banner)
                <tr wire:key="banner-{{ $banner->id }}">
                    <td class="px-6 py-3">
                        <img src="{{ $banner->imageUrl() }}" alt="{{ $banner->monthName() }}" class="w-16 h-10 object-cover rounded-md" />
                    </td>
                    <td class="px-6 py-3 font-medium" style="color: var(--text-primary);">{{ $banner->monthName() }}</td>
                    <td class="px-6 py-3"><x-ui.badge :variant="$banner->is_published ? 'success' : 'warning'">{{ $banner->is_published ? 'Publicado' : 'Rascunho' }}</x-ui.badge></td>
                    <td class="px-6 py-3 space-x-2 whitespace-nowrap">
                        <x-ui.tooltip text="Editar">
                            <button wire:click="edit({{ $banner->id }})" x-on:click="window.dispatchEvent(new CustomEvent('open-modal', { detail: 'birthday-banner-form' }))" class="w-9 h-9 inline-flex items-center justify-center rounded transition hover:brightness-95" style="background-color: rgba(37, 99, 235, 0.1); color: #2563EB;">
                                <i class="fa-solid fa-pen"></i>
                            </button>
                        </x-ui.tooltip>
                        @can('birthday-banners.update')
                            <x-ui.tooltip :text="$banner->is_published ? 'Despublicar' : 'Publicar'">
                                <button wire:click="togglePublish({{ $banner->id }})" class="w-9 h-9 inline-flex items-center justify-center rounded transition hover:brightness-95" style="background-color: rgba(217, 119, 6, 0.1); color: #D97706;">
                                    <i class="fa-solid fa-{{ $banner->is_published ? 'toggle-off' : 'toggle-on' }}"></i>
                                </button>
                            </x-ui.tooltip>
                        @endcan
                        @can('birthday-banners.delete')
                            <x-ui.tooltip text="Excluir">
                                <button @click="confirmAction('Confirma a exclusão deste banner?', () => $wire.delete({{ $banner->id }}))" class="w-9 h-9 inline-flex items-center justify-center rounded transition hover:brightness-95" style="background-color: rgba(220, 38, 38, 0.1); color: #DC2626;">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </x-ui.tooltip>
                        @endcan
                    </td>
                </tr>
            @endforeach
        </x-ui.data-table>
    @endif

    <!-- Modal: Formulário de Banner -->
    <x-ui.modal name="birthday-banner-form" maxWidth="lg">
        <x-slot:header>Banner de Aniversariantes</x-slot:header>
        <livewire:admin.birthdays.birthday-banner-form :banner="$editingBanner" :key="'birthday-banner-form-'.($editingBannerId ?? 'new')" />
    </x-ui.modal>
</div>
