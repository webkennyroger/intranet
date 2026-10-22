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
            <x-ui.button wire:click="closeForm" x-on:click="window.dispatchEvent(new CustomEvent('open-modal', { detail: 'video-form' }))">
                <i class="fa-solid fa-plus mr-1"></i> Novo vídeo
            </x-ui.button>
        </x-slot:actions>
    </x-admin.page-header>

    <!-- Estatísticas: um cartão só, ocupando a largura toda, dividido por
         uma linha fina — em vez de 2 cards de dashboard inteiros com um
         vão vazio depois deles. -->
    <div class="ui-card flex items-stretch divide-x divide-(--border-color)">
        <div class="flex items-center gap-3 px-5 py-4 flex-1">
            <span class="w-10 h-10 rounded-lg flex items-center justify-center shrink-0" style="background-color: color-mix(in srgb, #2563EB 14%, transparent);">
                <i class="fa-solid fa-video text-sm" style="color: #2563EB;"></i>
            </span>
            <div class="whitespace-nowrap">
                <p class="text-[10px] font-bold uppercase tracking-wide leading-none" style="color: var(--text-muted);">Total</p>
                <p class="text-xl font-black leading-tight" style="color: var(--text-primary);">{{ $totalVideos }}</p>
            </div>
        </div>

        <div class="flex items-center gap-3 px-5 py-4 flex-1">
            <span class="w-10 h-10 rounded-lg flex items-center justify-center shrink-0" style="background-color: color-mix(in srgb, #059669 14%, transparent);">
                <i class="fa-solid fa-check text-sm" style="color: #059669;"></i>
            </span>
            <div class="whitespace-nowrap">
                <p class="text-[10px] font-bold uppercase tracking-wide leading-none" style="color: var(--text-muted);">Publicados</p>
                <p class="text-xl font-black leading-tight" style="color: var(--text-primary);">{{ $publishedVideos }}</p>
            </div>
        </div>
    </div>

    <!-- Busca e filtro por status -->
    <div class="ui-card p-5">
        <div class="flex flex-col md:flex-row md:items-center gap-3">
            <div class="relative flex-1">
                <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-sm" style="color: var(--text-muted);"></i>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Buscar por título..."
                    class="w-full rounded-full pl-11 pr-4 py-2.5 text-sm placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-(--focus-ring)/40 transition"
                    style="background-color: var(--bg-elevated); border: 1px solid var(--border-color); color: var(--text-primary);" />
            </div>

            <div class="inline-flex items-center gap-1 rounded-full p-1 shrink-0" style="background-color: var(--bg-elevated); border: 1px solid var(--border-color);">
                @foreach (['all' => 'Todos', 'published' => 'Publicados', 'draft' => 'Rascunhos'] as $value => $label)
                    <button
                        type="button"
                        wire:click="$set('status', '{{ $value }}')"
                        class="rounded-full px-4 py-1.5 text-xs font-bold transition"
                        @if ($status === $value)
                            style="background-color: var(--admin-primary, var(--brand-primary, #0C4A34)); color: #fff;"
                        @else
                            style="color: var(--text-secondary);"
                        @endif
                    >{{ $label }}</button>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Tabela de Vídeos — isolada num island: selecionar linhas, paginar
         e publicar/excluir só atualizam esta região, sem re-renderizar o
         cabeçalho/estatísticas/busca da página. --}}
    @island(name: 'videos-list', always: true)
    <div class="space-y-4">
        @if (count($this->selected) > 0)
            <div class="flex items-center justify-end gap-2">
                <span class="text-xs font-semibold" style="color: var(--text-muted);">{{ count($this->selected) }} selecionado(s)</span>
                @can('videos.update')
                    <x-ui.button type="button" wire:click="bulkPublish" variant="secondary" size="sm">
                        <i class="fa-solid fa-check mr-1"></i> Publicar
                    </x-ui.button>
                    <x-ui.button type="button" wire:click="bulkUnpublish" variant="secondary" size="sm">
                        <i class="fa-solid fa-ban mr-1"></i> Despublicar
                    </x-ui.button>
                @endcan
                @can('videos.delete')
                    <x-ui.button type="button" @click="confirmAction('Confirma a exclusão de {{ count($this->selected) }} vídeo(s) selecionado(s)?', () => $wire.bulkDelete())" variant="danger" size="sm">
                        <i class="fa-solid fa-trash mr-1"></i> Excluir
                    </x-ui.button>
                @endcan
                <x-ui.button type="button" wire:click="deselectAll" variant="ghost" size="sm">Cancelar</x-ui.button>
            </div>
        @endif

        @if ($this->videos->isEmpty())
            <x-ui.empty-state title="Nenhum vídeo cadastrado ainda." icon="link" />
        @else
            <x-ui.data-table selectable>
                <x-slot:header>
                    <th class="px-4 py-3 w-8"><x-ui.table-checkbox header wire:click="toggleSelectAllOnPage" :checked="$this->allSelectedOnPage" /></th>
                    <th class="px-6 py-3"><i class="fa-solid fa-video mr-1 opacity-60"></i> Vídeo</th>
                    <th class="px-6 py-3"><i class="fa-solid fa-eye mr-1 opacity-60"></i> Visualizações</th>
                    <th class="px-6 py-3"><i class="fa-solid fa-circle-check mr-1 opacity-60"></i> Status</th>
                    <th class="px-6 py-3"><i class="fa-solid fa-bolt mr-1 opacity-60"></i> Ações</th>
                </x-slot:header>

                @foreach ($this->videos as $video)
                @php
                    $platformMeta = match ($video->platform()) {
                        'youtube' => ['icon' => 'youtube', 'label' => 'YouTube', 'color' => '#DC2626'],
                        'vimeo' => ['icon' => 'vimeo-v', 'label' => 'Vimeo', 'color' => '#2563EB'],
                        default => ['icon' => null, 'label' => 'Link externo', 'color' => '#64748B'],
                    };
                @endphp
                <tr wire:key="video-{{ $video->id }}">
                    <td class="px-4 py-3"><x-ui.table-checkbox wire:model.live="selected" value="{{ $video->id }}" /></td>
                    <td class="px-6 py-3">
                        <div class="flex items-center gap-3">
                            <div class="relative w-20 h-12 rounded-xl overflow-hidden shrink-0" style="background-color: var(--bg-elevated); border: 1px solid var(--border-color);">
                                @if ($video->thumbnailUrl())
                                    <img src="{{ $video->thumbnailUrl() }}" alt="{{ $video->title }}" class="w-full h-full object-cover">
                                @else
                                    <div class="w-full h-full flex items-center justify-center">
                                        <i class="fa-solid fa-video text-sm opacity-40"></i>
                                    </div>
                                @endif
                                <div class="absolute inset-0 flex items-center justify-center bg-black/15">
                                    <span class="w-6 h-6 rounded-full bg-white/90 flex items-center justify-center shadow-sm">
                                        <i class="fa-solid fa-play text-[9px]" style="color: #111;"></i>
                                    </span>
                                </div>
                            </div>
                            <div class="min-w-0">
                                <p class="font-semibold truncate max-w-xs" style="color: var(--text-primary);">{{ $video->title }}</p>
                                <a href="{{ $video->url }}" target="_blank" class="inline-flex items-center gap-1.5 text-xs mt-0.5 hover:underline" style="color: {{ $platformMeta['color'] }};">
                                    @if ($platformMeta['icon'])
                                        <i class="fa-brands fa-{{ $platformMeta['icon'] }}"></i>
                                    @else
                                        <i class="fa-solid fa-link"></i>
                                    @endif
                                    {{ $platformMeta['label'] }}
                                </a>
                            </div>
                        </div>
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
                    {{ $this->videos->links() }}
                </x-slot:footer>
            </x-ui.data-table>
        @endif
    </div>
    @endisland

    <!-- Modal: Formulário de Vídeo -->
    <x-ui.modal name="video-form" maxWidth="lg">
        <x-slot:header>
            <div class="flex items-center gap-3">
                <span class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0" style="background: linear-gradient(135deg, var(--admin-primary, var(--brand-primary, #0C4A34)), color-mix(in srgb, var(--admin-primary, var(--brand-primary, #0C4A34)) 70%, black));">
                    <i class="fa-solid fa-video text-white"></i>
                </span>
                <div>
                    <span class="text-[10px] font-bold uppercase tracking-wide" style="color: var(--admin-primary, var(--brand-primary, #0C4A34));">Vídeos</span>
                    <p class="font-black text-base leading-snug" style="color: var(--text-primary);">{{ $editingVideo ? 'Editar vídeo' : 'Novo vídeo' }}</p>
                </div>
            </div>
        </x-slot:header>
        <livewire:admin.videos.video-form :video="$editingVideo" :key="'video-form-'.($editingVideoId ?? 'new')" />
    </x-ui.modal>
</div>
