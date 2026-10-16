{{--
    Tela de gestão de Vídeos (admin).
    Lista os vídeos cadastrados com estatísticas e permite criar,
    editar, publicar/despublicar e excluir vídeos.
--}}
<div class="space-y-4">
    <!-- Cabeçalho da Página -->
    <x-admin.page-header
        icon="video"
        title="Vídeos"
        subtitle="Gerencie os vídeos exibidos no site."
        :breadcrumbs="[['label' => 'Painel', 'href' => route('dashboard')], ['label' => 'Vídeos']]"
    >
        <x-slot:actions>
            @if (count($selected) > 0)
                <span class="text-xs font-semibold" style="color: var(--text-muted);">{{ count($selected) }} selecionado(s)</span>
                @can('videos.update')
                    <x-ui.button type="button" wire:click="bulkPublish" variant="secondary" size="sm">
                        <i class="fa-solid fa-check mr-1"></i> Publicar
                    </x-ui.button>
                    <x-ui.button type="button" wire:click="bulkUnpublish" variant="secondary" size="sm">
                        <i class="fa-solid fa-ban mr-1"></i> Despublicar
                    </x-ui.button>
                @endcan
                @can('videos.delete')
                    <x-ui.button type="button" @click="confirmAction('Confirma a exclusão de {{ count($selected) }} vídeo(s) selecionado(s)?', () => $wire.bulkDelete())" variant="danger" size="sm">
                        <i class="fa-solid fa-trash mr-1"></i> Excluir
                    </x-ui.button>
                @endcan
                <x-ui.button type="button" wire:click="deselectAll" variant="ghost" size="sm">Cancelar</x-ui.button>
            @else
                <x-ui.button wire:click="closeForm" x-on:click="window.dispatchEvent(new CustomEvent('open-modal', { detail: 'video-form' }))">
                    Novo vídeo
                </x-ui.button>
            @endif
        </x-slot:actions>
    </x-admin.page-header>

    <!-- Estatísticas (Cards) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <x-admin.stat-card label="Total de vídeos" :value="$totalVideos" sublabel="Cadastrados" />
        <x-admin.stat-card label="Publicados" :value="$publishedVideos" sublabel="Visíveis no site" valueColor="var(--admin-primary-dark)" />
    </div>

    <!-- Busca -->
    <input type="text" wire:model.live.debounce.300ms="search" placeholder="Buscar por título..."
        class="w-full rounded-lg px-4 py-2.5 text-sm placeholder:text-slate-400"
        style="background-color: var(--bg-card); border: 1px solid var(--border-color); color: var(--text-primary);" />

    <!-- Tabela de Vídeos -->
    @if ($videos->isEmpty())
        <x-ui.empty-state title="Nenhum vídeo cadastrado ainda." icon="link" />
    @else
        <x-ui.data-table selectable>
            <x-slot:header>
                <th class="px-4 py-3 w-8"><x-ui.table-checkbox header wire:click="toggleSelectAllOnPage" :checked="$allSelectedOnPage" /></th>
                <th class="px-6 py-3"><i class="fa-solid fa-video mr-1 opacity-60"></i> Título</th>
                <th class="px-6 py-3"><i class="fa-solid fa-link mr-1 opacity-60"></i> URL</th>
                <th class="px-6 py-3"><i class="fa-solid fa-eye mr-1 opacity-60"></i> Visualizações</th>
                <th class="px-6 py-3"><i class="fa-solid fa-circle-check mr-1 opacity-60"></i> Status</th>
                <th class="px-6 py-3"><i class="fa-solid fa-bolt mr-1 opacity-60"></i> Ações</th>
            </x-slot:header>

            @foreach ($videos as $video)
                <tr wire:key="video-{{ $video->id }}">
                    <td class="px-4 py-3"><x-ui.table-checkbox wire:model.live="selected" value="{{ $video->id }}" /></td>
                    <td class="px-6 py-3">
                        <div class="flex items-center gap-2.5">
                            @if ($video->thumbnailUrl())
                                <img src="{{ $video->thumbnailUrl() }}" alt="{{ $video->title }}" class="w-14 h-10 rounded-lg object-cover shrink-0">
                            @else
                                <div class="w-14 h-10 rounded-lg flex items-center justify-center shrink-0" style="background-color: var(--bg-elevated);">
                                    <i class="fa-solid fa-video text-xs opacity-50"></i>
                                </div>
                            @endif
                            <span>{{ $video->title }}</span>
                        </div>
                    </td>
                    <td class="px-6 py-3">
                        <x-ui.tooltip text="Abrir vídeo">
                            <a href="{{ $video->url }}" target="_blank" class="w-9 h-9 inline-flex items-center justify-center rounded transition hover:brightness-95" style="background-color: rgba(5, 150, 105, 0.1); color: #059669;">
                                <i class="fa-solid fa-up-right-from-square"></i>
                            </a>
                        </x-ui.tooltip>
                    </td>
                    <td class="px-6 py-3">
                        <x-ui.badge size="sm"><i class="fa-regular fa-eye mr-1.5"></i>{{ $video->views_count }}</x-ui.badge>
                    </td>
                    <td class="px-6 py-3"><x-ui.badge :variant="$video->is_published ? 'success' : 'warning'">{{ $video->is_published ? 'Publicado' : 'Rascunho' }}</x-ui.badge></td>
                    <td class="px-6 py-3 space-x-2 whitespace-nowrap">
                        <x-ui.tooltip text="Editar">
                            <button wire:click="edit({{ $video->id }})" x-on:click="window.dispatchEvent(new CustomEvent('open-modal', { detail: 'video-form' }))" class="w-9 h-9 inline-flex items-center justify-center rounded transition hover:brightness-95" style="background-color: rgba(37, 99, 235, 0.1); color: #2563EB;">
                                <i class="fa-solid fa-pen"></i>
                            </button>
                        </x-ui.tooltip>
                        @can('videos.update')
                            <x-ui.tooltip :text="$video->is_published ? 'Despublicar' : 'Publicar'">
                                <button wire:click="togglePublish({{ $video->id }})" class="w-9 h-9 inline-flex items-center justify-center rounded transition hover:brightness-95" style="background-color: rgba(217, 119, 6, 0.1); color: #D97706;">
                                    <i class="fa-solid fa-{{ $video->is_published ? 'toggle-off' : 'toggle-on' }}"></i>
                                </button>
                            </x-ui.tooltip>
                        @endcan
                        @can('videos.delete')
                            <x-ui.tooltip text="Excluir">
                                <button @click="confirmAction('Confirma a exclusão deste vídeo?', () => $wire.delete({{ $video->id }}))" class="w-9 h-9 inline-flex items-center justify-center rounded transition hover:brightness-95" style="background-color: rgba(220, 38, 38, 0.1); color: #DC2626;">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </x-ui.tooltip>
                        @endcan
                    </td>
                </tr>
            @endforeach

            <x-slot:footer>
                {{ $videos->links() }}
            </x-slot:footer>
        </x-ui.data-table>
    @endif

    <!-- Modal: Formulário de Vídeo -->
    <x-ui.modal name="video-form" maxWidth="lg">
        <x-slot:header>Vídeo</x-slot:header>
        <livewire:admin.videos.video-form :video="$editingVideo" :key="'video-form-'.($editingVideoId ?? 'new')" />
    </x-ui.modal>
</div>
