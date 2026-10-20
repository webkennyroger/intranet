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
                <button
                    type="button"
                    wire:click="selectCategory({{ $category->id }})"
                    class="group relative overflow-hidden rounded-2xl p-5 flex items-center gap-4 text-left transition border {{ (string) $categoryId === (string) $category->id ? 'border-transparent shadow-md' : 'border-slate-200 dark:border-(--border-color) hover:border-slate-300' }}"
                    style="background-color: {{ (string) $categoryId === (string) $category->id ? $category->color : 'var(--bg-card)' }};"
                >
                    <span class="w-14 h-14 rounded-2xl flex items-center justify-center shrink-0 transition" style="background-color: {{ (string) $categoryId === (string) $category->id ? 'rgba(255,255,255,0.2)' : $category->lightColor() }};">
                        <i class="fa-solid fa-{{ $category->icon }} text-2xl" style="color: {{ (string) $categoryId === (string) $category->id ? '#fff' : $category->color }};"></i>
                    </span>
                    <div class="min-w-0">
                        <span class="font-bold text-base block {{ (string) $categoryId === (string) $category->id ? 'text-white' : 'text-slate-800 dark:text-(--text-primary)' }}">{{ $category->name }}</span>
                        <span class="text-xs block mt-0.5 {{ (string) $categoryId === (string) $category->id ? 'text-white/80' : 'text-slate-400 dark:text-(--text-muted)' }}">{{ $category->trainings_count }} {{ $category->trainings_count === 1 ? 'capacitação' : 'capacitações' }}</span>
                    </div>
                    @if ((string) $categoryId === (string) $category->id)
                        <i class="fa-solid fa-circle-check absolute top-3 right-3 text-white/90"></i>
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
                    class="ui-card flex flex-col overflow-hidden hover:shadow-md transition text-left w-full"
                >
                    <div class="h-1.5" style="background-color: {{ $accent }};"></div>
                    <div class="p-4 flex items-start gap-3">
                        <span class="w-11 h-11 rounded-xl flex items-center justify-center shrink-0" style="background-color: {{ $training->category?->lightColor() ?? 'rgba(12,74,52,0.1)' }};">
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
                        <div>
                            @if ($training->category)
                                <span class="text-[10px] font-bold uppercase tracking-wide" style="color: {{ $accent }};">{{ $training->category->name }}</span>
                            @endif
                            <p class="font-bold text-base" style="color: var(--text-primary);">{{ $training->title }}</p>
                        </div>
                    </x-slot:header>

                    @if ($training->description)
                        <p class="text-sm" style="color: var(--text-secondary);">{{ $training->description }}</p>
                    @endif

                    @if ($training->workload_hours || $training->starts_at)
                        <p class="mt-3 text-xs font-semibold" style="color: var(--text-muted);">
                            @if ($training->workload_hours) <i class="fa-solid fa-clock mr-1"></i>{{ $training->workload_hours }}h @endif
                            @if ($training->starts_at) · {{ $training->starts_at->format('d/m/Y') }} @endif
                        </p>
                    @endif

                    @php($links = $training->links ?? [])
                    @if (count($links))
                        <div class="mt-4 pt-3 border-t -mx-1.5 flex flex-col" style="border-color: var(--border-color);">
                            @foreach ($links as $i => $link)
                                <a
                                    href="{{ $link['url'] }}" target="_blank" rel="noopener" title="{{ $link['label'] ?: 'Acessar '.($i + 1) }}"
                                    class="group flex items-center gap-2 rounded-lg px-1.5 py-2 transition hover:bg-(--bg-hover)"
                                >
                                    <i class="fa-solid fa-chevron-right text-[9px] shrink-0" style="color: {{ $accent }};"></i>
                                    <span class="flex-1 min-w-0 truncate uppercase text-xs font-semibold" style="color: var(--text-primary);">{{ $link['label'] ?: (count($links) > 1 ? 'Acessar '.($i + 1) : 'Acessar') }}</span>
                                    <i class="fa-solid fa-arrow-up-right-from-square text-[10px] shrink-0" style="color: var(--text-muted);"></i>
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
