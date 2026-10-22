{{--
    Tela de Capacitações.
    Só existem duas pastas (Treinamento/Curso), então em vez da fileira
    densa de ladrilhos pequenos (como em Documentos), as pastas aparecem
    como 2 cards grandes de seleção, lado a lado — com busca e filtro de
    período abaixo e uma grade de cursos em cards ricos (ícone na cor da
    pasta, carga horária, data e botão de acesso).
--}}
<div class="mx-auto space-y-4">
    <!-- Cabeçalho da página -->
    <x-site.page-header icon="graduation-cap" title="Capacitações" subtitle="Cursos e treinamentos disponíveis para os servidores." />

    <!-- Pastas: 2 cards grandes de seleção -->
    @if ($categories->isNotEmpty())
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            @foreach ($categories as $category)
                @php $isSelected = (string) $categoryId === (string) $category->id; @endphp
                <button
                    type="button"
                    wire:click="selectCategory({{ $category->id }})"
                    class="ui-card group relative overflow-hidden p-5 flex items-center gap-4 text-left transition hover:-translate-y-0.5 hover:shadow-lg {{ $isSelected ? 'shadow-md' : '' }}"
                    style="background-color: {{ $isSelected ? $category->color : 'var(--bg-card)' }};"
                >
                    <span class="absolute left-0 right-0 top-0 h-1.5 transition-transform {{ $isSelected ? '' : 'scale-x-0 group-hover:scale-x-100' }}" style="background-color: {{ $isSelected ? 'rgba(255,255,255,0.5)' : $category->color }};"></span>

                    <span class="w-14 h-14 rounded-2xl flex items-center justify-center shrink-0 transition group-hover:scale-105" style="background-color: {{ $isSelected ? 'rgba(255,255,255,0.2)' : $category->lightColor() }};">
                        <i class="fa-solid fa-{{ $category->icon }} text-2xl" style="color: {{ $isSelected ? '#fff' : $category->color }};"></i>
                    </span>
                    <div class="min-w-0">
                        <span class="font-bold text-base block {{ $isSelected ? 'text-white' : '' }}" @if (! $isSelected) style="color: var(--text-primary);" @endif>{{ $category->name }}</span>
                        <span class="text-xs block mt-0.5 {{ $isSelected ? 'text-white/80' : '' }}" @if (! $isSelected) style="color: var(--text-muted);" @endif>{{ $category->trainings_count }} {{ $category->trainings_count === 1 ? 'capacitação' : 'capacitações' }}</span>
                    </div>
                    @if ($isSelected)
                        <i class="fa-solid fa-circle-check absolute top-4 right-4 text-white/90"></i>
                    @endif
                </button>
            @endforeach
        </div>
    @endif

    <!-- Filtros e busca -->
    <div class="ui-card p-5 sm:p-6">
        @if ($search !== '' || $categoryId !== '' || $period !== '')
            <div class="flex justify-end mb-3 -mt-1">
                <button type="button" wire:click="clearFilters" class="flex items-center gap-1.5 text-xs font-semibold text-slate-400 dark:text-(--text-muted) hover:text-rose-600 transition">
                    <i class="fa-solid fa-xmark"></i>
                    Limpar filtros
                </button>
            </div>
        @endif

        <div class="flex flex-col md:flex-row md:items-center gap-3">
            <div class="relative flex-1">
                <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-sm text-slate-400 dark:text-(--text-muted)"></i>
                <input
                    type="text"
                    wire:model.live.debounce.400ms="search"
                    placeholder="Buscar por palavra-chave..."
                    class="w-full rounded-full pl-11 pr-4 py-3 text-sm border border-slate-200 dark:border-(--border-color) bg-slate-50 dark:bg-(--bg-hover) dark:text-(--text-primary) focus:bg-white dark:focus:bg-(--bg-input) focus:outline-none focus:ring-2 focus:ring-(--focus-ring)/40 focus:border-(--focus-ring) transition-all duration-200"
                />
            </div>

            <div class="flex items-center gap-3">
                <div class="relative shrink-0">
                    <i class="fa-solid fa-calendar-days absolute left-4 top-1/2 -translate-y-1/2 text-sm text-slate-400 dark:text-(--text-muted) pointer-events-none z-10"></i>
                    <x-ui.select wire:model.live="period" placeholder="Período" class="rounded-full! pl-11! pr-8! py-3! border-slate-200! dark:border-(--border-color)! bg-slate-50! dark:bg-(--bg-hover)! dark:text-(--text-primary)! w-44">
                        <option value="7d">Últimos 7 dias</option>
                        <option value="30d">Últimos 30 dias</option>
                        <option value="year">Este ano</option>
                    </x-ui.select>
                </div>

                <button
                    type="button"
                    wire:click="$refresh"
                    class="inline-flex items-center justify-center gap-2 rounded-full px-6 py-3 text-sm font-bold text-white shadow-sm hover:brightness-110 active:brightness-95 transition shrink-0"
                    style="background-color: var(--brand-primary, #0C4A34);"
                >
                    <i class="fa-solid fa-magnifying-glass text-xs"></i>
                    <span>Buscar</span>
                </button>
            </div>
        </div>
    </div>

    {{-- Grade de capacitações — isolada num island: buscar, filtrar por
         período/pasta e paginar só atualizam esta região. --}}
    @island(name: 'trainings-grid', always: true)
    @if ($this->trainings->isEmpty())
        <!-- Estado vazio -->
        <div class="ui-card p-12 text-center text-slate-500 dark:text-(--text-secondary)">
            Nenhuma capacitação encontrada.
        </div>
    @else
        <!-- Grade de capacitações: cards compactos (ícone + pasta + título),
             tamanho padrão pra qualquer descrição; os detalhes completos
             (descrição, carga horária, links) abrem num modal ao clicar. -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach ($this->trainings as $training)
                @php($accent = $training->category?->color ?? 'var(--brand-primary, #0C4A34)')
                <button
                    type="button"
                    x-on:click="window.dispatchEvent(new CustomEvent('open-modal', { detail: 'training-{{ $training->id }}' }))"
                    class="ui-card group flex flex-col overflow-hidden hover:-translate-y-0.5 hover:shadow-lg transition text-left w-full"
                >
                    <div class="h-1.5" style="background-color: {{ $accent }};"></div>
                    <div class="p-4 flex items-start gap-3">
                        <span class="w-11 h-11 rounded-xl flex items-center justify-center shrink-0 transition group-hover:scale-105" style="background-color: {{ $training->category?->lightColor() ?? 'rgba(12,74,52,0.1)' }};">
                            <i class="fa-solid fa-graduation-cap text-lg" style="color: {{ $accent }};"></i>
                        </span>
                        <div class="min-w-0">
                            @if ($training->category)
                                <span class="text-[10px] font-bold uppercase tracking-wide" style="color: {{ $accent }};">{{ $training->category->name }}</span>
                            @endif
                            <span class="font-bold text-sm text-slate-800 dark:text-(--text-primary) leading-snug line-clamp-2">{{ $training->title }}</span>
                        </div>
                    </div>
                    <div class="px-4 pb-4 flex items-center justify-end gap-1 text-[11px] font-semibold" style="color: {{ $accent }};">
                        Ver detalhes <i class="fa-solid fa-chevron-right text-[9px]"></i>
                    </div>
                </button>

                <x-ui.modal :name="'training-'.$training->id" maxWidth="lg">
                    <x-slot:header>
                        <div class="flex items-center gap-3">
                            <span class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0" style="background-color: color-mix(in srgb, {{ $accent }} 14%, transparent);">
                                <i class="fa-solid fa-graduation-cap" style="color: {{ $accent }};"></i>
                            </span>
                            <div>
                                @if ($training->category)
                                    <span class="text-[10px] font-bold uppercase tracking-wide" style="color: {{ $accent }};">{{ $training->category->name }}</span>
                                @endif
                                <p class="font-black text-base leading-snug" style="color: var(--text-primary);">{{ $training->title }}</p>
                            </div>
                        </div>
                    </x-slot:header>

                    @if ($training->workload_hours || $training->starts_at)
                        <div class="flex flex-wrap items-center gap-2 mb-4">
                            @if ($training->workload_hours)
                                <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-xs font-bold" style="background-color: var(--bg-elevated); color: var(--text-secondary);">
                                    <i class="fa-solid fa-clock" style="color: {{ $accent }};"></i> {{ $training->workload_hours }}h
                                </span>
                            @endif
                            @if ($training->starts_at)
                                <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-xs font-bold" style="background-color: var(--bg-elevated); color: var(--text-secondary);">
                                    <i class="fa-solid fa-calendar-days" style="color: {{ $accent }};"></i> {{ $training->starts_at->format('d/m/Y') }}
                                </span>
                            @endif
                        </div>
                    @endif

                    @if ($training->description)
                        <p class="text-sm leading-relaxed" style="color: var(--text-secondary);">{{ $training->description }}</p>
                    @endif

                    @php($links = $training->links ?? [])
                    @if (count($links))
                        <div class="mt-4 space-y-2">
                            @foreach ($links as $i => $link)
                                <a
                                    href="{{ $link['url'] }}" target="_blank" rel="noopener" title="{{ $link['label'] ?: 'Acessar '.($i + 1) }}"
                                    class="flex items-center gap-3 rounded-xl p-3 transition hover:-translate-y-0.5 hover:shadow-md"
                                    style="background-color: var(--bg-elevated);"
                                >
                                    <span class="w-9 h-9 rounded-lg flex items-center justify-center shrink-0" style="background-color: color-mix(in srgb, {{ $accent }} 14%, transparent);">
                                        <i class="fa-solid fa-arrow-up-right-from-square text-xs" style="color: {{ $accent }};"></i>
                                    </span>
                                    <span class="flex-1 min-w-0 truncate text-sm font-bold" style="color: var(--text-primary);">{{ $link['label'] ?: (count($links) > 1 ? 'Acessar '.($i + 1) : 'Acessar treinamento') }}</span>
                                    <i class="fa-solid fa-chevron-right text-[10px] shrink-0" style="color: {{ $accent }};"></i>
                                </a>
                            @endforeach
                        </div>
                    @endif
                </x-ui.modal>
            @endforeach
        </div>

        <!-- Paginação -->
        <div class="pt-4">
            {{ $this->trainings->links() }}
        </div>
    @endif
    @endisland
</div>
