{{--
    Tela de Eventos: calendário mensal navegável com os eventos publicados
    de cada dia, mais uma coluna lateral com a agenda dos próximos eventos
    e a legenda de categorias. Clicar num evento (na grade ou na agenda)
    abre um modal com os detalhes completos. Cada evento usa a cor da sua
    categoria ($event->colorHex()) — igual ao calendário admin — em vez de
    pintar tudo com a mesma cor de marca, que deixava a tela monocromática.
--}}
<div class="space-y-4">
    <!-- Cabeçalho da página -->
    <x-site.page-header icon="calendar-days" title="Eventos" subtitle="Próximos eventos da Defensoria." />

    @php($weekDays = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'])

    <div class="grid grid-cols-1 lg:grid-cols-[1fr_300px] gap-4">
        <!-- Calendário -->
        <div class="ui-card overflow-hidden">
            <!-- Navegação do mês -->
            <div class="flex items-center justify-between min-h-19 px-4 sm:px-5 border-b border-slate-100 dark:border-(--border-color)">
                <div class="flex items-center gap-3">
                    <button type="button" wire:click="previousMonth" class="w-9 h-9 inline-flex items-center justify-center rounded-full transition hover:bg-slate-100 dark:hover:bg-(--bg-hover)" style="color: var(--text-secondary);" aria-label="Mês anterior">
                        <i class="fa-solid fa-chevron-left text-xs"></i>
                    </button>
                    <h2 class="text-xl font-black capitalize tracking-tight" style="color: var(--text-primary);">{{ $monthLabel }}</h2>
                    <button type="button" wire:click="nextMonth" class="w-9 h-9 inline-flex items-center justify-center rounded-full transition hover:bg-slate-100 dark:hover:bg-(--bg-hover)" style="color: var(--text-secondary);" aria-label="Próximo mês">
                        <i class="fa-solid fa-chevron-right text-xs"></i>
                    </button>
                </div>
                <button type="button" wire:click="goToToday" class="rounded-full px-4 py-2 text-xs font-bold transition hover:brightness-95" style="border: 1.5px solid var(--brand-primary, #0C4A34); color: var(--brand-primary, #0C4A34);">
                    Hoje
                </button>
            </div>

            <!-- Dias da semana -->
            <div class="grid grid-cols-7 border-b border-slate-100 dark:border-(--border-color)" style="background-color: var(--bg-elevated);">
                @foreach ($weekDays as $i => $day)
                    <div class="p-2.5 text-center text-[10px] font-bold uppercase tracking-wider" style="color: {{ in_array($i, [0, 6]) ? 'var(--text-muted)' : 'var(--text-secondary)' }};">{{ $day }}</div>
                @endforeach
            </div>

            <!-- Grade do mês -->
            <div class="grid grid-cols-7">
                @foreach ($weeks as $week)
                    @foreach ($week as $day)
                        <div class="min-h-28 p-1.5 sm:p-2 border-t border-r border-slate-100 dark:border-(--border-color) nth-[7n]:border-r-0 {{ ! $day['inMonth'] ? 'bg-slate-50/60 dark:bg-black/10' : '' }}" style="{{ $day['isToday'] ? 'background-color: color-mix(in srgb, var(--brand-primary, #0C4A34) 6%, transparent);' : '' }}">
                            <span class="inline-flex h-6 w-6 items-center justify-center rounded-full text-xs font-bold {{ $day['isToday'] ? 'text-white' : ($day['inMonth'] ? '' : 'opacity-40') }}"
                                style="{{ $day['isToday'] ? 'background-color: var(--brand-primary, #0C4A34);' : 'color: var(--text-secondary);' }}">
                                {{ $day['date']->day }}
                            </span>
                            <div class="mt-1 space-y-1">
                                @foreach ($day['events']->take(3) as $event)
                                    <button
                                        type="button"
                                        x-on:click="window.dispatchEvent(new CustomEvent('open-modal', { detail: 'event-{{ $event->id }}' }))"
                                        class="w-full truncate rounded-md px-1.5 py-0.5 text-left text-[10px] font-semibold transition hover:brightness-95"
                                        style="background-color: color-mix(in srgb, {{ $event->colorHex() }} 15%, white); color: {{ $event->colorHex() }};"
                                        title="{{ $event->title }}"
                                    >
                                        {{ $event->title }}
                                    </button>
                                @endforeach
                                @if ($day['events']->count() > 3)
                                    <span class="block text-[10px] font-semibold px-1.5" style="color: var(--text-muted);">+{{ $day['events']->count() - 3 }} mais</span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                @endforeach
            </div>
        </div>

        <!-- Coluna lateral: agenda dos próximos eventos -->
        <div class="ui-card overflow-hidden flex flex-col">
            <div class="flex items-center min-h-19 px-4 sm:px-5 border-b border-slate-100 dark:border-(--border-color)">
                <h2 class="font-black text-sm uppercase tracking-tight section-title-line flex items-center gap-2" style="color: var(--text-primary);">
                    <i class="fa-solid fa-calendar-check" style="color: var(--brand-primary-dark, var(--brand-primary));"></i> Próximos eventos
                </h2>
            </div>

            <div class="p-4 sm:p-5 space-y-2 flex-1">
                @forelse ($upcomingEvents as $event)
                    <button
                        type="button"
                        x-on:click="window.dispatchEvent(new CustomEvent('open-modal', { detail: 'event-{{ $event->id }}' }))"
                        class="w-full text-left rounded-xl p-3 transition hover:brightness-95"
                        style="background-color: var(--bg-elevated); border-left: 3px solid {{ $event->colorHex() }};"
                    >
                        <div class="flex items-center gap-1.5 text-[11px] font-bold" style="color: {{ $event->colorHex() }};">
                            {{ $event->starts_at->translatedFormat('d \d\e M') }} · {{ $event->starts_at->format('H:i') }}
                        </div>
                        <p class="text-sm font-bold mt-0.5 truncate" style="color: var(--text-primary);">{{ $event->title }}</p>
                    </button>
                @empty
                    <p class="text-xs" style="color: var(--text-muted);">Nenhum evento programado.</p>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Modais de detalhe de cada evento -->
    @foreach ($weeks->flatten(1)->flatMap(fn ($day) => $day['events'])->concat($upcomingEvents)->unique('id') as $event)
        <x-ui.modal :name="'event-'.$event->id" maxWidth="2xl">
            <x-slot:header>
                <div class="flex items-center gap-3">
                    <span class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0" style="background-color: color-mix(in srgb, {{ $event->colorHex() }} 14%, transparent);">
                        <i class="fa-solid fa-calendar-days" style="color: {{ $event->colorHex() }};"></i>
                    </span>
                    <div>
                        @if ($event->category)
                            <span class="text-[10px] font-bold uppercase tracking-wide" style="color: {{ $event->colorHex() }};">{{ $event->category->name }}</span>
                        @endif
                        <p class="font-black text-base leading-snug" style="color: var(--text-primary);">{{ $event->title }}</p>
                    </div>
                </div>
            </x-slot:header>

            @if ($event->coverImageUrl())
                <img src="{{ $event->coverImageUrl() }}" alt="{{ $event->title }}" class="w-full h-auto rounded-xl mb-4" style="box-shadow: 0 0 0 2px color-mix(in srgb, {{ $event->colorHex() }} 25%, transparent);">
            @endif

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div class="flex items-center gap-3 rounded-xl p-3" style="background-color: var(--bg-elevated);">
                    <span class="w-10 h-10 rounded-lg flex items-center justify-center shrink-0 text-white" style="background-color: {{ $event->colorHex() }};">
                        <i class="fa-solid fa-calendar-days"></i>
                    </span>
                    <div class="min-w-0">
                        <p class="text-xs" style="color: var(--text-muted);">Data e horário</p>
                        <p class="text-sm font-bold" style="color: var(--text-primary);">
                            {{ $event->starts_at->translatedFormat('d \d\e F') }} · {{ $event->starts_at->format('H:i') }}@if ($event->ends_at) às {{ $event->ends_at->format('H:i') }} @endif
                        </p>
                    </div>
                </div>
                @if ($event->location)
                    <div class="flex items-center gap-3 rounded-xl p-3" style="background-color: var(--bg-elevated);">
                        <span class="w-10 h-10 rounded-lg flex items-center justify-center shrink-0 text-white" style="background-color: {{ $event->colorHex() }};">
                            <i class="fa-solid fa-location-dot"></i>
                        </span>
                        <div class="min-w-0">
                            <p class="text-xs" style="color: var(--text-muted);">Local</p>
                            <p class="text-sm font-bold" style="color: var(--text-primary);">{{ $event->location }}</p>
                        </div>
                    </div>
                @endif
            </div>

            @if ($event->description)
                <p class="text-sm leading-relaxed mt-4" style="color: var(--text-secondary);">{{ $event->description }}</p>
            @endif

            @if ($event->link || $event->file_path)
                <div class="flex flex-wrap items-center gap-3 mt-4 pt-4 border-t" style="border-color: var(--border-color);">
                    @if ($event->link)
                        <a href="{{ $event->link }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 rounded-xl px-4 py-2 text-xs font-bold text-white transition hover:-translate-y-0.5 hover:shadow-md" style="background-color: {{ $event->colorHex() }};">
                            <i class="fa-solid fa-pen-to-square"></i> Realizar Inscrição
                        </a>
                    @endif
                    @if ($event->file_path)
                        <a href="{{ $event->fileUrl() }}" target="_blank" class="inline-flex items-center gap-2 rounded-xl px-4 py-2 text-xs font-bold transition hover:-translate-y-0.5 hover:shadow-md" style="background-color: var(--bg-elevated); color: var(--text-primary);">
                            <i class="fa-solid fa-{{ $event->fileIcon() }}"></i> Baixar anexo
                        </a>
                    @endif
                </div>
            @endif
        </x-ui.modal>
    @endforeach
</div>
