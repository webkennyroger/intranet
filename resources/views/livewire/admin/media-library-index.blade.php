{{--
Biblioteca de Mídia (admin): reúne todo arquivo real já enviado no
sistema (documentos, fotos de álbum) num layout "meu drive" — barra
lateral com busca/upload/origens, pastas por origem com contagem e
tamanho reais, lista de arquivos recentes e um resumo com gráfico de
rosca (imagens x documentos). Mural é posts/conteúdo editorial, não
arquivo de mídia — não entra aqui. O botão "Upload" só funciona depois
de escolher uma origem (Documentos ou Fotos) — essa origem define a
categoria do arquivo enviado. Só os itens enviados direto aqui podem
ser excluídos nesta tela — os demais pertencem a um conteúdo real.
Imagens abrem no x-ui.lightbox (componente reutilizável): tela cheia,
setas de navegação, loop, teclado (←/→/Esc) e swipe no touch.
--}}
@php
    $imageItems = $media->where('type', 'image')->map(fn($item) => ['url' => $item['url'], 'name' => $item['name']])->values();

    $totalForChart = max($totalCount, 1);
    $circumference = 2 * M_PI * 40;

    // Ponto num ângulo (fração de 0 a 1, medida a partir do topo em sentido
    // horário — mesmo ponto de partida do círculo "-rotate-90" usado no
    // desenho das fatias) e num raio específico. Usado tanto pro rótulo de
    // porcentagem (raio 40, no meio do anel) quanto pras linhas divisórias
    // entre fatias (de um raio a outro, atravessando a espessura do anel).
    $donutPoint = fn(float $fraction, float $radius = 40) => [
        'x' => 50 + $radius * sin($fraction * 2 * M_PI),
        'y' => 50 - $radius * cos($fraction * 2 * M_PI),
    ];

    // Uma fatia por origem real (Documentos, Fotos) — não por tipo (imagem
    // x documento) — pra bater com a lista de abaixo e
    // cada origem aparecer com sua própria cor. As fatias ficam encostadas
    // (sem "folga" no próprio arco — isso deixava o corte mais aberto na
    // borda externa do anel do que na interna); a separação entre elas é
    // desenhada depois, como uma linha reta de largura constante.
    $cumulativeDash = 0;
    $donutSegments = collect($sourceStats)
        ->filter(fn (array $stat) => $stat['count'] > 0)
        ->map(function (array $stat) use (&$cumulativeDash, $circumference, $totalForChart, $donutPoint) {
            $dash = $circumference * ($stat['count'] / $totalForChart);
            $segment = [
                'source' => $stat['source'],
                'color' => \App\Livewire\Admin\MediaLibraryIndex::sourceColor($stat['source']),
                'pct' => round($stat['count'] / $totalForChart * 100),
                'dash' => $dash,
                'offset' => -$cumulativeDash,
                'boundaryFraction' => $cumulativeDash / $circumference,
                'label' => $donutPoint(($cumulativeDash + $dash / 2) / $circumference),
            ];
            $cumulativeDash += $dash;

            return $segment;
        })
        ->values();

    // Uma divisória reta (raio 31 a 49, ou seja, de ponta a ponta da
    // espessura do anel de 18) no início de cada fatia — como as fatias
    // estão encostadas, isso cobre também a junção entre a última e a
    // primeira.
    $donutDividers = $donutSegments->map(fn (array $segment) => [
        'inner' => $donutPoint($segment['boundaryFraction'], 31),
        'outer' => $donutPoint($segment['boundaryFraction'], 49),
    ]);
@endphp
<div class="space-y-4" x-data="{
        preview: null,
        selectedFile: null,
        openPreview(item) {
            this.preview = item;
            window.dispatchEvent(new CustomEvent('open-modal', { detail: 'media-preview' }));
        },
        deleteSelectedFile() {
            const file = this.selectedFile;
            if (!file || !file.canDelete) return;

            confirmAction('Confirma a exclusão deste arquivo?', () => {
                $wire.deleteItem(file.ownerType, file.ownerId, file.fieldIndex ?? null);
                this.selectedFile = null;
            });
        },
        onItemRenamed(detail) {
            const file = this.selectedFile;
            if (file && file.ownerType === detail.ownerType && String(file.ownerId) === String(detail.ownerId)) {
                file.name = detail.name;
            }
        },
    }"
    x-on:media-item-renamed.window="onItemRenamed($event.detail)"
>
    <!-- Cabeçalho da Página -->
    <x-admin.page-header icon="photo-film" title="Biblioteca de Mídia"
        subtitle="Documentos e fotos enviados no sistema."
        :breadcrumbs="[['label' => 'Painel', 'href' => route('dashboard')], ['label' => 'Biblioteca de Mídia']]" />

    <div class="grid grid-cols-1 lg:grid-cols-[1fr_380px] gap-4 items-start">
        <!-- Área Central: pastas por origem + busca + arquivos -->
        <div class="space-y-4 min-w-0">
            <!-- Pastas (uma por origem, com contagem e tamanho reais) -->
            <div>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                    @foreach ($sourceStats as $stat)
                        <button wire:click="$set('source', '{{ $stat['source'] }}')"
                            class="text-left rounded-xl p-3 shadow-sm hover:shadow-md transition-all {{ $source === $stat['source'] ? 'ring-2' : '' }}"
                            style="background-color: var(--bg-card); border: 1px solid var(--border-color); {{ $source === $stat['source'] ? 'ring-color: var(--admin-primary);' : '' }}">
                            <div class="w-9 h-9 rounded-lg flex items-center justify-center mb-2"
                                style="background-color: {{ \App\Livewire\Admin\MediaLibraryIndex::sourceColor($stat['source']) }}1A;">
                                <i class="fa-solid fa-{{ \App\Livewire\Admin\MediaLibraryIndex::sourceIcon($stat['source']) }}"
                                    style="color: {{ \App\Livewire\Admin\MediaLibraryIndex::sourceColor($stat['source']) }};"></i>
                            </div>
                            <div class="text-sm font-semibold truncate" style="color: var(--text-primary);">
                                {{ $stat['source'] }}</div>
                            <div class="text-xs mt-0.5" style="color: var(--text-muted);">{{ $stat['count'] }}
                                {{ $stat['count'] === 1 ? 'arquivo' : 'arquivos' }} ·
                                {{ \App\Livewire\Admin\MediaLibraryIndex::formatSize($stat['size']) }}</div>
                        </button>
                    @endforeach
                </div>
            </div>

            <!-- Busca -->
            <div class="relative">
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Buscar mídia..."
                    class="w-full rounded-xl pl-10 pr-4 py-2.5 text-sm"
                    style="background-color: var(--bg-input); border: 1px solid var(--border-color); color: var(--text-primary);" />
                <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-sm"
                    style="color: var(--text-muted);"></i>
            </div>

            <!-- Arquivos -->
            <div>
                <div class="flex items-center justify-between mb-2">
                    <div class="text-sm font-bold" style="color: var(--text-primary);">
                        {{ $source === '' ? 'Arquivos recentes' : 'Arquivos — ' . $source }}</div>
                    <div class="flex items-center gap-3">
                        @if ($view === 'list' && count($selected) > 0)
                            <span class="text-xs font-semibold" style="color: var(--text-muted);">{{ count($selected) }} selecionado(s)</span>
                            <button type="button" @click="confirmAction('Confirma a exclusão de {{ count($selected) }} arquivo(s) selecionado(s)?', () => $wire.bulkDelete())" class="text-xs font-semibold text-red-600 hover:underline">
                                <i class="fa-solid fa-trash mr-1"></i> Excluir selecionados
                            </button>
                            <button type="button" wire:click="deselectAll" class="text-xs font-semibold hover:underline" style="color: var(--text-muted);">Cancelar</button>
                        @endif
                        <div class="flex items-center gap-1 p-1 rounded-xl shrink-0"
                            style="background-color: var(--bg-elevated);">
                            <button wire:click="$set('view', 'grid')"
                                class="px-3 py-1.5 rounded-lg text-sm font-semibold whitespace-nowrap cursor-pointer transition {{ $view === 'grid' ? 'shadow-sm' : 'hover:bg-slate-200 dark:hover:bg-white/10' }}"
                                style="{{ $view === 'grid' ? 'background-color: var(--bg-card); color: var(--text-primary);' : 'color: var(--text-secondary);' }}">
                                <i class="fa-solid fa-grip mr-1"></i> Grade
                            </button>
                            <button wire:click="$set('view', 'list')"
                                class="px-3 py-1.5 rounded-lg text-sm font-semibold whitespace-nowrap cursor-pointer transition {{ $view === 'list' ? 'shadow-sm' : 'hover:bg-slate-200 dark:hover:bg-white/10' }}"
                                style="{{ $view === 'list' ? 'background-color: var(--bg-card); color: var(--text-primary);' : 'color: var(--text-secondary);' }}">
                                <i class="fa-solid fa-list mr-1"></i> Lista
                            </button>
                        </div>
                    </div>
                </div>

                @if ($media->isEmpty())
                    <!-- Estado Vazio -->
                    <x-ui.empty-state title="Nenhum arquivo encontrado." icon="photo-film" />
                @elseif ($view === 'grid')
                    <!-- Grade de Mídia -->
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                        @php $imageIndex = 0; @endphp
                        @foreach ($media as $item)
                            <div class="group rounded-xl p-3 shadow-sm hover:shadow-md transition-all"
                                style="background-color: var(--bg-card); border: 1px solid var(--border-color);"
                                wire:key="media-{{ $item['path'] }}">
                                @if ($item['type'] === 'image')
                                    {{-- Imagens abrem no lightbox nativo, com navegação entre todas as imagens da página --}}
                                    <div @click="window.dispatchEvent(new CustomEvent('open-lightbox', { detail: { items: @js($imageItems), index: {{ $imageIndex }} } }))"
                                        class="relative grid aspect-square place-items-center rounded-lg overflow-hidden cursor-pointer"
                                        style="background-color: var(--bg-elevated);">
                                        <img src="{{ $item['url'] }}" class="w-full h-full object-cover"
                                            alt="{{ $item['name'] }}" />

                                        <span class="absolute left-1.5 top-1.5 text-[10px] font-semibold px-2 py-0.5 rounded-full"
                                            style="background-color: var(--bg-card); color: var(--text-secondary);">
                                            <i
                                                class="fa-solid fa-{{ \App\Livewire\Admin\MediaLibraryIndex::sourceIcon($item['source']) }} mr-1"></i>{{ $item['source'] }}
                                        </span>

                                        @if ($item['canDelete'])
                                            <button
                                                @click.stop="confirmAction('Confirma a exclusão deste arquivo?', () => $wire.{{ \App\Livewire\Admin\MediaLibraryIndex::deleteAction($item) }})"
                                                class="absolute right-1.5 top-1.5 w-7 h-7 rounded-md flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity"
                                                style="background-color: var(--bg-card); color: var(--text-primary);" title="Excluir">
                                                <i class="fa-solid fa-trash text-xs"></i>
                                            </button>
                                        @endif
                                    </div>
                                    @php $imageIndex++; @endphp
                                @else
                                    {{-- Documentos abrem na prévia simples (lightbox é só pra imagem) --}}
                                    <div @click="openPreview(@js(['url' => $item['url'], 'name' => $item['name'], 'type' => $item['type'], 'size' => \App\Livewire\Admin\MediaLibraryIndex::formatSize($item['size']), 'date' => $item['date']->diffForHumans(), 'source' => $item['source']]))"
                                        class="relative grid aspect-square place-items-center rounded-lg overflow-hidden cursor-pointer bg-linear-to-br {{ $item['gradient'] }}">
                                        <i class="fa-solid fa-{{ $item['icon'] }} text-2xl"
                                            style="color: var(--text-secondary);"></i>

                                        <span class="absolute left-1.5 top-1.5 text-[10px] font-semibold px-2 py-0.5 rounded-full"
                                            style="background-color: var(--bg-card); color: var(--text-secondary);">
                                            <i
                                                class="fa-solid fa-{{ \App\Livewire\Admin\MediaLibraryIndex::sourceIcon($item['source']) }} mr-1"></i>{{ $item['source'] }}
                                        </span>

                                        @if ($item['canDelete'])
                                            <button
                                                @click.stop="confirmAction('Confirma a exclusão deste arquivo?', () => $wire.{{ \App\Livewire\Admin\MediaLibraryIndex::deleteAction($item) }})"
                                                class="absolute right-1.5 top-1.5 w-7 h-7 rounded-md flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity"
                                                style="background-color: var(--bg-card); color: var(--text-primary);" title="Excluir">
                                                <i class="fa-solid fa-trash text-xs"></i>
                                            </button>
                                        @endif
                                    </div>
                                @endif
                                <div class="mt-2.5 truncate text-xs font-semibold" style="color: var(--text-primary);">
                                    {{ $item['name'] }}</div>
                                <div class="mt-1 flex items-center justify-between text-[10px]"
                                    style="color: var(--text-muted);">
                                    <span>{{ \App\Livewire\Admin\MediaLibraryIndex::formatSize($item['size']) }}</span>
                                    <span>{{ $item['date']->diffForHumans() }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-4">
                        {{ $media->links() }}
                    </div>
                @else
                    <!-- Lista de Mídia -->
                    <x-ui.data-table selectable>
                        <x-slot:header>
                            <th class="px-4 py-3 w-8"><x-ui.table-checkbox header wire:click="toggleSelectAllOnPage" :checked="count($selected) > 0 && array_diff($media->filter(fn ($item) => $item['canDelete'])->map(fn ($item) => \App\Livewire\Admin\MediaLibraryIndex::selectionKey($item))->all(), $selected) === []" /></th>
                            <th class="px-6 py-3"><i class="fa-solid fa-file mr-1 opacity-60"></i> Arquivo</th>
                            <th class="px-6 py-3"><i class="fa-solid fa-weight-hanging mr-1 opacity-60"></i> Detalhes</th>
                            <th class="px-6 py-3"><i class="fa-solid fa-bolt mr-1 opacity-60"></i> Ações</th>
                        </x-slot:header>

                        @php $imageIndex = 0; @endphp
                        @foreach ($media as $item)
                            @php
                                $viewPayload = \Illuminate\Support\Js::from([
                                    'url' => $item['url'],
                                    'name' => $item['name'],
                                    'type' => $item['type'],
                                    'icon' => $item['icon'] ?? null,
                                    'gradient' => $item['gradient'] ?? null,
                                    'size' => \App\Livewire\Admin\MediaLibraryIndex::formatSize($item['size']),
                                    'date' => $item['date']->format('d/m/Y H:i'),
                                    'source' => $item['source'],
                                    'path' => $item['path'],
                                    'mediaId' => $item['mediaId'],
                                    'ownerType' => $item['ownerType'],
                                    'ownerId' => $item['ownerId'],
                                    'fieldIndex' => $item['fieldIndex'],
                                    'canRename' => $item['canRename'],
                                    'canDelete' => $item['canDelete'],
                                ]);
                            @endphp
                            <tr wire:key="media-row-{{ $item['path'] }}">
                                <td class="px-4 py-3">
                                    @if ($item['canDelete'])
                                        <x-ui.table-checkbox wire:model.live="selected" value="{{ \App\Livewire\Admin\MediaLibraryIndex::selectionKey($item) }}" />
                                    @endif
                                </td>
                                <td class="px-6 py-3">
                                    @if ($item['type'] === 'image')
                                        <button type="button"
                                            @click="window.dispatchEvent(new CustomEvent('open-lightbox', { detail: { items: @js($imageItems), index: {{ $imageIndex }} } }))"
                                            class="flex items-center gap-3 hover:underline cursor-pointer"
                                            style="color: var(--text-primary);">
                                            <span
                                                class="w-9 h-9 rounded-lg flex items-center justify-center shrink-0 overflow-hidden"
                                                style="background-color: var(--bg-elevated);">
                                                <img src="{{ $item['url'] }}" class="w-full h-full object-cover"
                                                    alt="{{ $item['name'] }}" />
                                            </span>
                                            <span class="truncate max-w-56 font-medium">{{ $item['name'] }}</span>
                                        </button>
                                        @php $imageIndex++; @endphp
                                    @else
                                        <button type="button"
                                            @click="openPreview(@js(['url' => $item['url'], 'name' => $item['name'], 'type' => $item['type'], 'size' => \App\Livewire\Admin\MediaLibraryIndex::formatSize($item['size']), 'date' => $item['date']->diffForHumans(), 'source' => $item['source']]))"
                                            class="flex items-center gap-3 hover:underline cursor-pointer"
                                            style="color: var(--text-primary);">
                                            <span
                                                class="w-9 h-9 rounded-lg flex items-center justify-center shrink-0 overflow-hidden bg-linear-to-br {{ $item['gradient'] }}">
                                                <i class="fa-solid fa-{{ $item['icon'] }} text-sm"
                                                    style="color: var(--text-secondary);"></i>
                                            </span>
                                            <span class="truncate max-w-56 font-medium">{{ $item['name'] }}</span>
                                        </button>
                                    @endif
                                </td>
                                <td class="px-6 py-3 whitespace-nowrap">
                                    <x-ui.badge variant="info"><i
                                            class="fa-solid fa-{{ \App\Livewire\Admin\MediaLibraryIndex::sourceIcon($item['source']) }} mr-1"></i>{{ $item['source'] }}</x-ui.badge>
                                    <span class="text-xs ml-1"
                                        style="color: var(--text-muted);">{{ \App\Livewire\Admin\MediaLibraryIndex::formatSize($item['size']) }}
                                        · {{ $item['date']->diffForHumans() }}</span>
                                </td>
                                <td class="px-6 py-3">
                                    <x-ui.dropdown align="right" width="48">
                                        <x-slot:trigger>
                                            <button type="button" class="w-8 h-8 rounded-md flex items-center justify-center cursor-pointer transition hover:bg-slate-200 dark:hover:bg-white/10" style="color: var(--text-primary);">
                                                <x-ui.icon name="ellipsis-vertical" class="w-5 h-5" />
                                            </button>
                                        </x-slot:trigger>

                                        <x-ui.context-item icon="eye" @click="selectedFile = {{ $viewPayload }}">Ver</x-ui.context-item>
                                        @if ($item['canRename'])
                                            <x-ui.context-item icon="pencil" @click="$wire.openRenameModal({{ \Illuminate\Support\Js::from($item['ownerType']) }}, {{ $item['ownerId'] }}, {{ \Illuminate\Support\Js::from($item['name']) }})">Renomear</x-ui.context-item>
                                        @endif
                                        @if ($item['canDelete'])
                                            <x-ui.context-item icon="trash" danger @click="confirmAction('Confirma a exclusão deste arquivo?', () => $wire.{{ \App\Livewire\Admin\MediaLibraryIndex::deleteAction($item) }})">Excluir</x-ui.context-item>
                                        @endif
                                    </x-ui.dropdown>
                                </td>
                            </tr>
                        @endforeach

                        <x-slot:footer>
                            {{ $media->links() }}
                        </x-slot:footer>
                    </x-ui.data-table>
                @endif
            </div>
        </div>

        <!-- Barra Lateral Direita: upload + resumo com gráfico de rosca (como no Velzon file-manager) -->
        <div class="space-y-4 lg:sticky lg:top-4">
            <x-ui.card>
                <x-ui.file-upload
                    wire:model="newMediaFiles"
                    multiple
                    :disabled="$source === ''"
                    :error="$errors->first('newMediaFiles') ?: $errors->first('newMediaFiles.*')"
                    :hint="$source === '' ? 'Selecione uma origem abaixo antes de enviar.' : 'Pode selecionar ou arrastar um ou vários arquivos de uma vez.'"
                />
            </x-ui.card>

            <x-ui.card>
            <template x-if="!selectedFile">
                <div>
                <div class="flex items-center pb-3" style="border-bottom: 1px dashed var(--border-color);">
                    <h3 class="flex-1 text-sm font-semibold" style="color: var(--text-primary);">Visão Geral</h3>
                </div>

                <div class="flex items-center justify-center py-4">
                    <div
                        class="relative w-58 h-58"
                        x-data="{ tooltip: null, tx: 0, ty: 0 }"
                        x-ref="donutWrap"
                        @mousemove="if (tooltip) { const r = $refs.donutWrap.getBoundingClientRect(); tx = $event.clientX - r.left; ty = $event.clientY - r.top; }"
                    >
                        <svg viewBox="0 0 100 100" class="w-full h-full -rotate-90">
                            {{-- Base do anel sólida (--bg-surface): só aparece se não houver
                                 arquivo nenhum. Clicar nela limpa o filtro de origem. --}}
                            <circle cx="50" cy="50" r="40" fill="none" stroke="var(--bg-surface)" stroke-width="18" class="cursor-pointer" wire:click="$set('source', '')" />
                            @foreach ($donutSegments as $segment)
                                {{-- Clicar numa fatia filtra a lista de arquivos por aquela origem,
                                     igual a clicar no card/botão da mesma origem. As fatias ficam
                                     encostadas (sem gap no próprio arco) — a separação entre elas é
                                     a linha divisória desenhada no outro <svg>, de largura constante. --}}
                                <circle
                                    cx="50" cy="50" r="40" fill="none" stroke="{{ $segment['color'] }}"
                                    stroke-width="18"
                                    stroke-dasharray="{{ $segment['dash'] }} {{ $circumference }}"
                                    stroke-dashoffset="{{ $segment['offset'] }}"
                                    class="cursor-pointer transition-[stroke-width] duration-150"
                                    :style="tooltip === @js($segment['source']) ? 'stroke-width: 20px;' : ''"
                                    style="transform-box: fill-box; transform-origin: center;"
                                    @mouseenter="tooltip = @js($segment['source'])" @mouseleave="tooltip = null"
                                    wire:click="$set('source', '{{ $segment['source'] }}')"
                                />
                            @endforeach
                        </svg>
                        {{-- Divisórias entre fatias (largura constante, de ponta a ponta do
                             anel) e rótulos de porcentagem: svg sem a rotação, pra o texto não
                             ficar deitado e a divisória ficar no ângulo certo. --}}
                        <svg viewBox="0 0 100 100" class="absolute inset-0 w-full h-full pointer-events-none">
                            @foreach ($donutDividers as $divider)
                                <line x1="{{ $divider['inner']['x'] }}" y1="{{ $divider['inner']['y'] }}" x2="{{ $divider['outer']['x'] }}" y2="{{ $divider['outer']['y'] }}" stroke="var(--bg-surface)" stroke-width="3" stroke-linecap="round" />
                            @endforeach
                            @foreach ($donutSegments as $segment)
                                @if ($segment['pct'] > 0)
                                    <text x="{{ $segment['label']['x'] }}" y="{{ $segment['label']['y'] }}" text-anchor="middle" dominant-baseline="middle" class="fill-white font-bold" style="font-size: 7.5px;">{{ $segment['pct'] }}%</text>
                                @endif
                            @endforeach
                        </svg>
                        <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                            <span class="text-xl font-bold" style="color: var(--text-primary);">{{ $totalCount }}</span>
                            <span class="text-[10px]" style="color: var(--text-muted);">arquivos</span>
                        </div>

                        {{-- Tooltip que acompanha o cursor enquanto passa o mouse na fatia, com a cor dela --}}
                        <div
                            x-show="tooltip" x-cloak
                            class="absolute z-10 whitespace-nowrap rounded-lg px-3 py-1.5 text-xs font-bold text-white shadow-lg pointer-events-none"
                            :style="`left: ${tx}px; top: ${ty - 14}px; transform: translate(-50%, -100%); background-color: ${@js($donutSegments->pluck('color', 'source'))[tooltip]};`"
                        >
                            @foreach ($donutSegments as $segment)
                                <span x-show="tooltip === @js($segment['source'])">{{ $segment['source'] }}: {{ \App\Livewire\Admin\MediaLibraryIndex::formatSize($sourceStats->firstWhere('source', $segment['source'])['size']) }}</span>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-center flex-wrap gap-x-4 gap-y-1.5 text-xs pb-3 mb-4"
                    style="border-bottom: 1px dashed var(--border-color); color: var(--text-secondary);">
                    @foreach ($donutSegments as $segment)
                        <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full"
                                style="background-color: {{ $segment['color'] }};"></span> {{ $segment['source'] }} ({{ $segment['pct'] }}%)</span>
                    @endforeach
                </div>

                <ul class="list-none space-y-4">
                    @foreach ($sourceStats as $stat)
                        <li class="flex items-center">
                            <div class="shrink-0">
                                <div class="w-8 h-8 rounded flex items-center justify-center"
                                    style="background-color: {{ \App\Livewire\Admin\MediaLibraryIndex::sourceColor($stat['source']) }}1A;">
                                    <i class="fa-solid fa-{{ \App\Livewire\Admin\MediaLibraryIndex::sourceIcon($stat['source']) }} text-sm"
                                        style="color: {{ \App\Livewire\Admin\MediaLibraryIndex::sourceColor($stat['source']) }};"></i>
                                </div>
                            </div>
                            <div class="flex-1 min-w-0 ml-3">
                                <h5 class="mb-0.5 text-sm font-semibold truncate" style="color: var(--text-primary);">
                                    {{ $stat['source'] }}</h5>
                                <p class="mb-0 text-xs" style="color: var(--text-muted);">{{ $stat['count'] }}
                                    {{ $stat['count'] === 1 ? 'arquivo' : 'arquivos' }}</p>
                            </div>
                            <b class="text-sm shrink-0"
                                style="color: var(--text-primary);">{{ \App\Livewire\Admin\MediaLibraryIndex::formatSize($stat['size']) }}</b>
                        </li>
                    @endforeach
                </ul>

                <div class="mt-4 pt-3 flex items-center justify-between text-sm"
                    style="border-top: 1px dashed var(--border-color);">
                    <span style="color: var(--text-secondary);"><i class="fa-solid fa-database mr-1.5"></i>Espaço
                        usado</span>
                    <span class="font-semibold"
                        style="color: var(--text-primary);">{{ \App\Livewire\Admin\MediaLibraryIndex::formatSize($totalSize) }}</span>
                </div>
                </div>
            </template>

            <!-- Prévia do Arquivo: substitui a Visão Geral quando um arquivo é selecionado via "Ver" na tabela -->
            <template x-if="selectedFile">
                <div>
                    <div class="flex items-center pb-3" style="border-bottom: 1px dashed var(--border-color);">
                        <h3 class="flex-1 text-sm font-semibold" style="color: var(--text-primary);">Prévia do Arquivo</h3>
                        <button type="button" @click="selectedFile = null" class="w-7 h-7 rounded-md flex items-center justify-center" style="background-color: var(--bg-elevated); color: var(--text-muted);">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <div class="py-4">
                        <div
                            class="rounded-xl aspect-square flex items-center justify-center overflow-hidden mb-3"
                            style="background-color: var(--bg-elevated);"
                            :class="selectedFile?.type === 'image' ? 'cursor-pointer' : ''"
                            @click="selectedFile?.type === 'image' && window.dispatchEvent(new CustomEvent('open-lightbox', { detail: { items: [{ url: selectedFile.url, name: selectedFile.name }], index: 0 } }))"
                        >
                            <template x-if="selectedFile?.type === 'image'">
                                <img :src="selectedFile?.url" :alt="selectedFile?.name" class="w-full h-full object-cover">
                            </template>
                            <template x-if="selectedFile?.type !== 'image'">
                                <i class="fa-solid text-3xl" :class="'fa-' + selectedFile?.icon" style="color: var(--text-secondary);"></i>
                            </template>
                        </div>

                        <div class="text-sm font-semibold truncate" style="color: var(--text-primary);" x-text="selectedFile?.name"></div>
                        <div class="text-xs mt-0.5" style="color: var(--text-muted);" x-text="(selectedFile?.size ?? '') + ' · ' + (selectedFile?.date ?? '')"></div>
                    </div>

                    <div class="pt-3" style="border-top: 1px dashed var(--border-color);">
                        <div class="text-xs font-bold uppercase tracking-wide mb-3" style="color: var(--text-muted);">Descrição do Arquivo</div>
                        <div class="grid grid-cols-[2fr_3fr] gap-y-3.5 gap-x-3 text-sm">
                            <span style="color: var(--text-muted);">Nome</span>
                            <span class="font-medium truncate" style="color: var(--text-primary);" x-text="selectedFile?.name"></span>

                            <span style="color: var(--text-muted);">Tipo</span>
                            <span class="font-medium" style="color: var(--text-primary);" x-text="selectedFile?.type === 'image' ? 'Imagem' : 'Documento'"></span>

                            <span style="color: var(--text-muted);">Tamanho</span>
                            <span class="font-medium" style="color: var(--text-primary);" x-text="selectedFile?.size"></span>

                            <span style="color: var(--text-muted);">Criado em</span>
                            <span class="font-medium" style="color: var(--text-primary);" x-text="selectedFile?.date"></span>

                            <span style="color: var(--text-muted);">Caminho</span>
                            <span class="font-medium truncate" style="color: var(--text-primary);" x-text="selectedFile?.path"></span>

                            <span style="color: var(--text-muted);">Origem</span>
                            <span class="font-medium" style="color: var(--text-primary);" x-text="selectedFile?.source"></span>
                        </div>
                    </div>

                    <div class="flex items-center justify-center gap-2 mt-4">
                        <x-ui.tooltip text="Abrir em nova aba">
                            <a :href="selectedFile?.url" target="_blank" class="w-10 h-10 shrink-0 inline-flex items-center justify-center rounded transition hover:brightness-95" style="background-color: rgba(37, 99, 235, 0.1); color: #2563EB;">
                                <i class="fa-solid fa-up-right-from-square"></i>
                            </a>
                        </x-ui.tooltip>
                        @if ($media->contains(fn ($mediaItem) => $mediaItem['canRename']))
                        <template x-if="selectedFile?.canRename">
                            <x-ui.tooltip text="Renomear">
                                <button type="button" @click="$wire.openRenameModal(selectedFile.ownerType, selectedFile.ownerId, selectedFile.name)" class="w-10 h-10 shrink-0 inline-flex items-center justify-center rounded transition hover:brightness-95" style="background-color: rgba(217, 119, 6, 0.1); color: #D97706;">
                                    <i class="fa-solid fa-pen"></i>
                                </button>
                            </x-ui.tooltip>
                        </template>
                        @endif
                        @if ($media->contains(fn ($mediaItem) => $mediaItem['canDelete']))
                        <template x-if="selectedFile?.canDelete">
                            <x-ui.tooltip text="Excluir">
                                <button type="button" @click="deleteSelectedFile()" class="w-10 h-10 shrink-0 inline-flex items-center justify-center rounded" style="background-color: rgb(254 226 226); color: rgb(185 28 28);">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </x-ui.tooltip>
                        </template>
                        @endif
                    </div>
                </div>
            </template>
            </x-ui.card>
        </div>
    </div>

    <!-- Modal: Renomear arquivo (só pros enviados direto aqui, via Media) -->
    <x-ui.modal name="media-rename" maxWidth="sm">
        <x-slot:header>Renomear arquivo</x-slot:header>

        <form wire:submit="renameItem" class="space-y-4">
            <x-ui.input wire:model="renamingName" label="Nome do arquivo" autofocus />

            <div class="flex justify-end gap-2">
                <x-ui.button type="button" variant="ghost" x-on:click="window.dispatchEvent(new CustomEvent('close-modal', { detail: 'media-rename' }))">Cancelar</x-ui.button>
                <x-ui.button type="submit">Salvar</x-ui.button>
            </div>
        </form>
    </x-ui.modal>

    <!-- Modal: Prévia de Documento (imagens usam o lightbox nativo, abaixo) -->
    <x-ui.modal name="media-preview" maxWidth="2xl">
        <x-slot:header>
            <span x-text="preview?.name"></span>
        </x-slot:header>

        <div class="space-y-4" x-show="preview">
            <div class="rounded-xl p-10 text-center" style="background-color: var(--bg-elevated);">
                <i class="fa-solid fa-file-lines text-4xl" style="color: var(--text-muted);"></i>
                <p class="text-sm mt-2" style="color: var(--text-secondary);">Pré-visualização não disponível para este
                    tipo de arquivo.</p>
            </div>

            <div class="flex items-center justify-between text-xs" style="color: var(--text-muted);">
                <span x-text="preview?.source"></span>
                <span x-text="(preview?.size ?? '') + ' · ' + (preview?.date ?? '')"></span>
            </div>

            <a :href="preview?.url" target="_blank"
                class="inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold text-white hover:brightness-110 transition"
                style="background-color: var(--admin-primary);">
                <i class="fa-solid fa-up-right-from-square"></i> Abrir em nova aba
            </a>
        </div>
    </x-ui.modal>

    <x-ui.lightbox />
</div>