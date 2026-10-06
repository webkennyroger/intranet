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
    >
        <x-slot:actions>
            <x-ui.button wire:click="closeForm" x-on:click="window.dispatchEvent(new CustomEvent('open-modal', { detail: 'video-form' }))">
                Novo vídeo
            </x-ui.button>
        </x-slot:actions>
    </x-admin.page-header>

    <!-- Estatísticas (Cards) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <x-admin.stat-card label="Total de vídeos" :value="$totalVideos" sublabel="Cadastrados" />
        <x-admin.stat-card label="Publicados" :value="$publishedVideos" sublabel="Visíveis no site" valueColor="var(--admin-primary-dark)" />
    </div>

    <!-- Tabela de Vídeos -->
    @if ($videos->isEmpty())
        <x-ui.empty-state title="Nenhum vídeo cadastrado ainda." icon="link" />
    @else
        <x-ui.data-table selectable>
            <x-slot:header>
                <th class="px-4 py-3 w-8"><x-ui.table-checkbox header /></th>
                <th class="px-6 py-3"><i class="fa-solid fa-video mr-1 opacity-60"></i> Título</th>
                <th class="px-6 py-3"><i class="fa-solid fa-link mr-1 opacity-60"></i> URL</th>
                <th class="px-6 py-3"><i class="fa-solid fa-circle-check mr-1 opacity-60"></i> Status</th>
                <th class="px-6 py-3"><i class="fa-solid fa-bolt mr-1 opacity-60"></i> Ações</th>
            </x-slot:header>

            @foreach ($videos as $video)
                <tr wire:key="video-{{ $video->id }}">
                    <td class="px-4 py-3"><x-ui.table-checkbox /></td>
                    <td class="px-6 py-3">{{ $video->title }}</td>
                    <td class="px-6 py-3">
                        <a href="{{ $video->url }}" target="_blank" class="text-emerald-700 hover:underline truncate max-w-xs block">{{ $video->url }}</a>
                    </td>
                    <td class="px-6 py-3"><x-ui.badge :variant="$video->is_published ? 'success' : 'warning'">{{ $video->is_published ? 'Publicado' : 'Rascunho' }}</x-ui.badge></td>
                    <td class="px-6 py-3 space-x-3 whitespace-nowrap">
                        <x-ui.tooltip text="Editar">
                            <button wire:click="edit({{ $video->id }})" x-on:click="window.dispatchEvent(new CustomEvent('open-modal', { detail: 'video-form' }))" class="text-emerald-700">
                                <i class="fa-solid fa-pen"></i>
                            </button>
                        </x-ui.tooltip>
                        @can('content.publish')
                            <x-ui.tooltip :text="$video->is_published ? 'Despublicar' : 'Publicar'">
                                <button wire:click="togglePublish({{ $video->id }})" class="text-amber-700">
                                    <i class="fa-solid fa-{{ $video->is_published ? 'toggle-off' : 'toggle-on' }}"></i>
                                </button>
                            </x-ui.tooltip>
                        @endcan
                        @can('content.delete')
                            <x-ui.tooltip text="Excluir">
                                <button wire:click="delete({{ $video->id }})" wire:confirm="Confirma a exclusão deste vídeo?" class="text-red-700">
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
