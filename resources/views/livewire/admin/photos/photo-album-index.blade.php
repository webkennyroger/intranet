{{--
    Tela de gestão de Álbuns de Fotos (admin).
    Lista os álbuns cadastrados com estatísticas e permite criar,
    editar, publicar/despublicar e excluir álbuns (e suas fotos). O
    ícone "Ver fotos" abre uma grade com todas as fotos do álbum (modal
    grande, com rolagem própria); clicar numa foto da grade abre ela no
    lightbox nativo, por cima, já navegável pelas outras — sem precisar
    entrar no formulário de edição.
--}}
<div class="space-y-4">
    <!-- Cabeçalho da Página -->
    <x-admin.page-header
        icon="images"
        title="Fotos"
        subtitle="Gerencie os álbuns de fotos exibidos no site."
        :breadcrumbs="[['label' => 'Painel', 'href' => route('dashboard')], ['label' => 'Fotos']]"
    >
        <x-slot:actions>
            <x-ui.button wire:click="closeForm" x-on:click="window.dispatchEvent(new CustomEvent('open-modal', { detail: 'photo-album-form' }))">
                Novo álbum
            </x-ui.button>
        </x-slot:actions>
    </x-admin.page-header>

    <!-- Estatísticas: um cartão só, ocupando a largura toda, dividido por
         uma linha fina — em vez de 3 cards de dashboard inteiros com um
         vão vazio depois deles. -->
    <div class="ui-card flex items-stretch divide-x divide-(--border-color)">
        <div class="flex items-center gap-3 px-5 py-4 flex-1">
            <span class="w-10 h-10 rounded-lg flex items-center justify-center shrink-0" style="background-color: color-mix(in srgb, #2563EB 14%, transparent);">
                <i class="fa-solid fa-images text-sm" style="color: #2563EB;"></i>
            </span>
            <div class="whitespace-nowrap">
                <p class="text-[10px] font-bold uppercase tracking-wide leading-none" style="color: var(--text-muted);">Total de álbuns</p>
                <p class="text-xl font-black leading-tight" style="color: var(--text-primary);">{{ $totalAlbums }}</p>
            </div>
        </div>

        <div class="flex items-center gap-3 px-5 py-4 flex-1">
            <span class="w-10 h-10 rounded-lg flex items-center justify-center shrink-0" style="background-color: color-mix(in srgb, #059669 14%, transparent);">
                <i class="fa-solid fa-check text-sm" style="color: #059669;"></i>
            </span>
            <div class="whitespace-nowrap">
                <p class="text-[10px] font-bold uppercase tracking-wide leading-none" style="color: var(--text-muted);">Publicados</p>
                <p class="text-xl font-black leading-tight" style="color: var(--text-primary);">{{ $publishedAlbums }}</p>
            </div>
        </div>

        <div class="flex items-center gap-3 px-5 py-4 flex-1">
            <span class="w-10 h-10 rounded-lg flex items-center justify-center shrink-0" style="background-color: color-mix(in srgb, #9333EA 14%, transparent);">
                <i class="fa-solid fa-image text-sm" style="color: #9333EA;"></i>
            </span>
            <div class="whitespace-nowrap">
                <p class="text-[10px] font-bold uppercase tracking-wide leading-none" style="color: var(--text-muted);">Total de fotos</p>
                <p class="text-xl font-black leading-tight" style="color: var(--text-primary);">{{ $totalPhotos }}</p>
            </div>
        </div>
    </div>

    <!-- Busca -->
    <div class="ui-card p-5">
        <div class="flex flex-col md:flex-row md:items-center gap-3">
            <div class="relative flex-1">
                <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-sm" style="color: var(--text-muted);"></i>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Buscar por título do álbum..."
                    class="w-full rounded-full pl-11 pr-4 py-2.5 text-sm placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-(--focus-ring)/40 transition"
                    style="background-color: var(--bg-elevated); border: 1px solid var(--border-color); color: var(--text-primary);" />
            </div>
            <button type="button" wire:click="$refresh" class="inline-flex items-center justify-center gap-2 rounded-full px-6 py-2.5 text-sm font-bold text-white shadow-sm hover:brightness-110 active:brightness-95 transition shrink-0" style="background-color: var(--admin-primary, var(--brand-primary, #0C4A34));">
                <i class="fa-solid fa-magnifying-glass text-xs"></i>
                <span>Buscar</span>
            </button>
        </div>
    </div>

    {{-- Tabela de Álbuns — isolada num island: selecionar linhas, paginar
         e publicar/excluir só atualizam esta região. --}}
    @island(name: 'albums-list', always: true)
    <div class="space-y-4">
        @if (count($this->selected) > 0)
            <div class="flex items-center justify-end gap-2">
                <span class="text-xs font-semibold" style="color: var(--text-muted);">{{ count($this->selected) }} selecionado(s)</span>
                @can('photos.update')
                    <x-ui.button type="button" wire:click="bulkPublish" variant="secondary" size="sm">
                        <i class="fa-solid fa-check mr-1"></i> Publicar
                    </x-ui.button>
                    <x-ui.button type="button" wire:click="bulkUnpublish" variant="secondary" size="sm">
                        <i class="fa-solid fa-ban mr-1"></i> Despublicar
                    </x-ui.button>
                @endcan
                @can('photos.delete')
                    <x-ui.button type="button" @click="confirmAction('Confirma a exclusão de {{ count($this->selected) }} álbum(ns) selecionado(s)?', () => $wire.bulkDelete())" variant="danger" size="sm">
                        <i class="fa-solid fa-trash mr-1"></i> Excluir
                    </x-ui.button>
                @endcan
                <x-ui.button type="button" wire:click="deselectAll" variant="ghost" size="sm">Cancelar</x-ui.button>
            </div>
        @endif

        @if ($this->albums->isEmpty())
            <x-ui.empty-state title="Nenhum álbum cadastrado ainda." icon="photo" />
        @else
            <x-ui.data-table selectable>
                <x-slot:header>
                    <th class="px-4 py-3 w-8"><x-ui.table-checkbox header wire:click="toggleSelectAllOnPage" :checked="$this->allSelectedOnPage" /></th>
                    <th class="px-6 py-3"><i class="fa-solid fa-images mr-1 opacity-60"></i> Álbum</th>
                    <th class="px-6 py-3"><i class="fa-solid fa-calendar-days mr-1 opacity-60"></i> Data</th>
                    <th class="px-6 py-3"><i class="fa-solid fa-image mr-1 opacity-60"></i> Fotos</th>
                    <th class="px-6 py-3"><i class="fa-solid fa-eye mr-1 opacity-60"></i> Visualizações</th>
                    <th class="px-6 py-3"><i class="fa-solid fa-circle-check mr-1 opacity-60"></i> Status</th>
                    <th class="px-6 py-3"><i class="fa-solid fa-bolt mr-1 opacity-60"></i> Ações</th>
                </x-slot:header>

                @foreach ($this->albums as $album)
                <tr wire:key="photo-album-{{ $album->id }}">
                    <td class="px-4 py-3"><x-ui.table-checkbox wire:model.live="selected" value="{{ $album->id }}" /></td>
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
                    <td class="px-6 py-3">
                        <x-ui.badge size="sm"><i class="fa-regular fa-image mr-1.5"></i>{{ $album->photos_count }}</x-ui.badge>
                    </td>
                    <td class="px-6 py-3">
                        <x-ui.badge size="sm"><i class="fa-regular fa-eye mr-1.5"></i>{{ $album->views_count }}</x-ui.badge>
                    </td>
                    <td class="px-6 py-3"><x-ui.badge :variant="$album->is_published ? 'success' : 'warning'">{{ $album->is_published ? 'Publicado' : 'Rascunho' }}</x-ui.badge></td>
                    <td class="px-6 py-3 space-x-2 whitespace-nowrap">
                        @if ($album->photos_count > 0)
                            <x-ui.tooltip text="Ver fotos">
                                <button type="button" wire:click="view({{ $album->id }})" x-on:click="window.dispatchEvent(new CustomEvent('open-modal', { detail: 'photo-album-gallery' }))" class="w-9 h-9 inline-flex items-center justify-center rounded transition hover:brightness-95" style="background-color: rgba(8, 145, 178, 0.1); color: #0891B2;">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                            </x-ui.tooltip>
                        @endif
                        <x-ui.tooltip text="Editar">
                            <button wire:click="edit({{ $album->id }})" x-on:click="window.dispatchEvent(new CustomEvent('open-modal', { detail: 'photo-album-form' }))" class="w-9 h-9 inline-flex items-center justify-center rounded transition hover:brightness-95" style="background-color: rgba(37, 99, 235, 0.1); color: #2563EB;">
                                <i class="fa-solid fa-pen"></i>
                            </button>
                        </x-ui.tooltip>
                        @can('photos.update')
                            <x-ui.tooltip :text="$album->is_published ? 'Despublicar' : 'Publicar'">
                                <button wire:click="togglePublish({{ $album->id }})" class="w-9 h-9 inline-flex items-center justify-center rounded transition hover:brightness-95" style="background-color: rgba(217, 119, 6, 0.1); color: #D97706;">
                                    <i class="fa-solid fa-{{ $album->is_published ? 'toggle-off' : 'toggle-on' }}"></i>
                                </button>
                            </x-ui.tooltip>
                        @endcan
                        @can('photos.delete')
                            <x-ui.tooltip text="Excluir">
                                <button @click="confirmAction('Confirma a exclusão deste álbum e de todas as suas fotos?', () => $wire.delete({{ $album->id }}))" class="w-9 h-9 inline-flex items-center justify-center rounded transition hover:brightness-95" style="background-color: rgba(220, 38, 38, 0.1); color: #DC2626;">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </x-ui.tooltip>
                        @endcan
                    </td>
                </tr>
            @endforeach

                <x-slot:footer>
                    {{ $this->albums->links() }}
                </x-slot:footer>
            </x-ui.data-table>
        @endif

        {{-- Modal de edição/criação: precisa estar DENTRO do island — "Editar"
             (por linha) dispara a ação de dentro do island, então só o
             conteúdo do island é re-renderizado; um modal fora dele nunca
             receberia o álbum novo (ficava sempre com os dados do primeiro
             carregamento, ou vazio pra álbum nenhum). --}}
        <x-ui.modal name="photo-album-form" maxWidth="2xl">
            <x-slot:header>
                <div class="flex items-center gap-3">
                    <span class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0" style="background: linear-gradient(135deg, var(--admin-primary, var(--brand-primary, #0C4A34)), color-mix(in srgb, var(--admin-primary, var(--brand-primary, #0C4A34)) 70%, black));">
                        <i class="fa-solid fa-images text-white"></i>
                    </span>
                    <div>
                        <span class="text-[10px] font-bold uppercase tracking-wide" style="color: var(--admin-primary, var(--brand-primary, #0C4A34));">Fotos</span>
                        <p class="font-black text-base leading-snug" style="color: var(--text-primary);">{{ $this->editingAlbum ? 'Editar álbum' : 'Novo álbum' }}</p>
                    </div>
                </div>
            </x-slot:header>
            <livewire:admin.photos.photo-album-form :album="$this->editingAlbum" :key="'photo-album-form-'.($this->editingAlbumId ?? 'new')" />
        </x-ui.modal>

        {{-- Modal "Ver fotos": grade com todas as fotos do álbum e rolagem
             própria — grande de propósito, pra ver bem as fotos. Clicar numa
             foto abre ela no lightbox (por cima deste modal), já na posição
             certa, podendo navegar pelas outras com as setas/teclado. Mesmo
             motivo do island do modal de edição acima: "Ver fotos" dispara
             de dentro do island. --}}
        <x-ui.modal name="photo-album-gallery" maxWidth="6xl">
            <x-slot:header>{{ $this->viewingAlbum?->title }}</x-slot:header>
            @if ($this->viewingAlbum)
                @php
                    $galleryItems = $this->viewingAlbum->photos->map(fn ($photo) => [
                        'url' => \Illuminate\Support\Facades\Storage::url($photo->image_path),
                        'name' => $photo->caption ?: $this->viewingAlbum->title,
                    ]);
                @endphp
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3 max-h-[70vh] overflow-y-auto pr-1">
                    @foreach ($this->viewingAlbum->photos as $index => $photo)
                        <button
                            type="button"
                            @click="window.dispatchEvent(new CustomEvent('open-lightbox', { detail: { items: @js($galleryItems), index: {{ $index }} } }))"
                            class="group relative aspect-square rounded-xl overflow-hidden"
                            wire:key="gallery-photo-{{ $photo->id }}"
                        >
                            <img src="{{ \Illuminate\Support\Facades\Storage::url($photo->image_path) }}" alt="{{ $photo->caption ?: $this->viewingAlbum->title }}" class="w-full h-full object-cover transition group-hover:scale-105" />
                        </button>
                    @endforeach
                </div>
            @endif
        </x-ui.modal>
    </div>
    @endisland

    <x-ui.lightbox />
</div>
