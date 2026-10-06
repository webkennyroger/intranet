{{--
    Tela de gestão de Álbuns de Fotos (admin).
    Lista os álbuns cadastrados com estatísticas e permite criar,
    editar, publicar/despublicar e excluir álbuns (e suas fotos).
--}}
<div class="space-y-4">
    <!-- Cabeçalho da Página -->
    <x-admin.page-header
        icon="images"
        title="Fotos"
        subtitle="Gerencie os álbuns de fotos exibidos no site."
    >
        <x-slot:actions>
            <x-ui.button wire:click="closeForm" x-on:click="window.dispatchEvent(new CustomEvent('open-modal', { detail: 'photo-album-form' }))">
                Novo álbum
            </x-ui.button>
        </x-slot:actions>
    </x-admin.page-header>

    <!-- Estatísticas (Cards) -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <x-admin.stat-card label="Total de álbuns" :value="$totalAlbums" sublabel="Cadastrados" />
        <x-admin.stat-card label="Publicados" :value="$publishedAlbums" sublabel="Visíveis no site" valueColor="var(--admin-primary-dark)" />
        <x-admin.stat-card label="Total de fotos" :value="$totalPhotos" sublabel="Em todos os álbuns" />
    </div>

    <!-- Tabela de Álbuns -->
    @if ($albums->isEmpty())
        <x-ui.empty-state title="Nenhum álbum cadastrado ainda." icon="photo" />
    @else
        <x-ui.data-table selectable>
            <x-slot:header>
                <th class="px-4 py-3 w-8"><x-ui.table-checkbox header /></th>
                <th class="px-6 py-3"><i class="fa-solid fa-images mr-1 opacity-60"></i> Álbum</th>
                <th class="px-6 py-3"><i class="fa-solid fa-calendar-days mr-1 opacity-60"></i> Data</th>
                <th class="px-6 py-3"><i class="fa-solid fa-image mr-1 opacity-60"></i> Fotos</th>
                <th class="px-6 py-3"><i class="fa-solid fa-circle-check mr-1 opacity-60"></i> Status</th>
                <th class="px-6 py-3"><i class="fa-solid fa-bolt mr-1 opacity-60"></i> Ações</th>
            </x-slot:header>

            @foreach ($albums as $album)
                <tr wire:key="photo-album-{{ $album->id }}">
                    <td class="px-4 py-3"><x-ui.table-checkbox /></td>
                    <td class="px-6 py-3">
                        <div class="flex items-center gap-2.5">
                            @if ($album->cover_image_path)
                                <img src="{{ \Illuminate\Support\Facades\Storage::url($album->cover_image_path) }}" alt="{{ $album->title }}" class="w-10 h-10 rounded-lg object-cover">
                            @else
                                <div class="w-10 h-10 rounded-lg flex items-center justify-center" style="background-color: var(--bg-elevated);">
                                    <i class="fa-solid fa-image text-sm opacity-50"></i>
                                </div>
                            @endif
                            <span class="font-semibold" style="color: var(--text-primary);">{{ $album->title }}</span>
                        </div>
                    </td>
                    <td class="px-6 py-3">{{ $album->event_date?->format('d/m/Y') ?? '—' }}</td>
                    <td class="px-6 py-3">{{ $album->photos_count }}</td>
                    <td class="px-6 py-3"><x-ui.badge :variant="$album->is_published ? 'success' : 'warning'">{{ $album->is_published ? 'Publicado' : 'Rascunho' }}</x-ui.badge></td>
                    <td class="px-6 py-3 space-x-3 whitespace-nowrap">
                        <x-ui.tooltip text="Editar">
                            <button wire:click="edit({{ $album->id }})" x-on:click="window.dispatchEvent(new CustomEvent('open-modal', { detail: 'photo-album-form' }))" class="text-emerald-700">
                                <i class="fa-solid fa-pen"></i>
                            </button>
                        </x-ui.tooltip>
                        @can('content.publish')
                            <x-ui.tooltip :text="$album->is_published ? 'Despublicar' : 'Publicar'">
                                <button wire:click="togglePublish({{ $album->id }})" class="text-amber-700">
                                    <i class="fa-solid fa-{{ $album->is_published ? 'toggle-off' : 'toggle-on' }}"></i>
                                </button>
                            </x-ui.tooltip>
                        @endcan
                        @can('content.delete')
                            <x-ui.tooltip text="Excluir">
                                <button wire:click="delete({{ $album->id }})" wire:confirm="Confirma a exclusão deste álbum e de todas as suas fotos?" class="text-red-700">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </x-ui.tooltip>
                        @endcan
                    </td>
                </tr>
            @endforeach

            <x-slot:footer>
                {{ $albums->links() }}
            </x-slot:footer>
        </x-ui.data-table>
    @endif

    <!-- Modal: Formulário de Álbum -->
    <x-ui.modal name="photo-album-form" maxWidth="2xl">
        <x-slot:header>Álbum de Fotos</x-slot:header>
        <livewire:admin.photos.photo-album-form :album="$editingAlbum" :key="'photo-album-form-'.($editingAlbumId ?? 'new')" />
    </x-ui.modal>
</div>
