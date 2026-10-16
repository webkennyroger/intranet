{{--
    Tela de Eventos: calendário mensal navegável com os eventos publicados
    de cada dia, mais uma lista dos próximos eventos abaixo. Clicar num
    evento (na grade ou na lista) abre um modal com os detalhes completos.
--}}
<div class="space-y-4">
    <!-- Cabeçalho da página -->
    <x-site.page-header icon="calendar-days" title="Eventos" subtitle="Próximos eventos da Defensoria." />

    @php($weekDays = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'])

    <!-- Calendário -->
    <div class="ui-card overflow-hidden">
        <!-- Navegação do mês -->
        <div class="flex items-center justify-between p-4 border-b border-slate-100 dark:border-(--border-color)">
            <div class="flex items-center gap-2">
                <button type="button" wire:click="previousMonth" class="w-8 h-8 inline-flex items-center justify-center rounded-md transition hover:bg-slate-100 dark:hover:bg-(--bg-hover)" style="color: var(--text-primary);" aria-label="Mês anterior">
                    <i class="fa-solid fa-chevron-left text-xs"></i>
                </button>
                <span class="text-sm font-bold capitalize" style="color: var(--text-primary);">{{ $monthLabel }}</span>
                <button type="button" wire:click="nextMonth" class="w-8 h-8 inline-flex items-center justify-center rounded-md transition hover:bg-slate-100 dark:hover:bg-(--bg-hover)" style="color: var(--text-primary);" aria-label="Próximo mês">
                    <i class="fa-solid fa-chevron-right text-xs"></i>
                </button>
            </div>
            <button type="button" wire:click="goToToday" class="rounded-md px-3 py-1.5 text-xs font-bold transition hover:brightness-95" style="background-color: var(--brand-primary-light); color: var(--brand-primary-dark);">
                Hoje
            </button>
        </div>

        <!-- Dias da semana -->
        <div class="grid grid-cols-7 border-b border-slate-100 dark:border-(--border-color)" style="background-color: var(--bg-elevated);">
            @foreach ($weekDays as $day)
                <div class="p-2 text-center text-[10px] font-bold uppercase tracking-wider" style="color: var(--text-muted);">{{ $day }}</div>
            @endforeach
        </div>

        <!-- Grade do mês -->
        <div class="grid grid-cols-7">
            @foreach ($weeks as $week)
                @foreach ($week as $day)
                    <div class="min-h-24 p-1.5 sm:p-2 border-t border-r border-slate-100 dark:border-(--border-color) nth-[7n]:border-r-0 {{ ! $day['inMonth'] ? 'bg-slate-50/50 dark:bg-black/10' : '' }}">
                        <span class="inline-flex h-6 w-6 items-center justify-center rounded-full text-xs {{ $day['isToday'] ? 'font-bold text-white' : ($day['inMonth'] ? '' : 'opacity-40') }}"
                            style="{{ $day['isToday'] ? 'background-color: var(--brand-primary, #0C4A34);' : 'color: var(--text-secondary);' }}">
                            {{ $day['date']->day }}
                        </span>
                        <div class="mt-1 space-y-1">
                            @foreach ($day['events'] as $event)
                                <button
                                    type="button"
                                    x-on:click="window.dispatchEvent(new CustomEvent('open-modal', { detail: 'event-{{ $event->id }}' }))"
                                    class="w-full truncate rounded-md px-1.5 py-0.5 text-left text-[10px] font-semibold transition hover:brightness-95"
                                    style="background-color: var(--brand-primary-light); color: var(--brand-primary-dark);"
                                    title="{{ $event->title }}"
                                >
                                    {{ $event->title }}
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            @endforeach
        </div>
    </div>

    <!-- Próximos eventos -->
    @if ($upcomingEvents->isNotEmpty())
        <div class="ui-card p-5">
            <h3 class="text-sm font-bold mb-3" style="color: var(--text-primary);">Próximos eventos</h3>
            <ul class="space-y-2">
                @foreach ($upcomingEvents as $event)
                    <li>
                        <button
                            type="button"
                            x-on:click="window.dispatchEvent(new CustomEvent('open-modal', { detail: 'event-{{ $event->id }}' }))"
                            class="w-full flex items-center gap-3 rounded-xl p-3 text-left transition hover:bg-slate-50 dark:hover:bg-(--bg-hover)"
                            style="border: 1px solid var(--border-color);"
                        >
                            <span class="text-[11px] font-bold shrink-0 w-16" style="color: var(--text-muted);">{{ $event->starts_at->translatedFormat('d/m') }}</span>
                            <span class="text-sm font-medium truncate flex-1" style="color: var(--text-primary);">{{ $event->title }}</span>
                            <span class="inline-flex items-center rounded px-1.5 h-5 text-[10px] font-bold shrink-0" style="background-color: var(--brand-primary-light); color: var(--brand-primary-dark);">evento</span>
                        </button>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Modais de detalhe de cada evento -->
    @foreach ($weeks->flatten(1)->flatMap(fn ($day) => $day['events'])->merge($upcomingEvents)->unique('id') as $event)
        <x-ui.modal :name="'event-'.$event->id" maxWidth="lg">
            <x-slot:header>
                <p class="font-bold text-base" style="color: var(--text-primary);">{{ $event->title }}</p>
            </x-slot:header>

            <div class="space-y-3 text-sm" style="color: var(--text-secondary);">
                <div class="flex items-center gap-2">
                    <i class="fa-regular fa-calendar-days w-4" style="color: var(--brand-primary, #0C4A34);"></i>
                    <span>
                        {{ $event->starts_at->translatedFormat('d \d\e F \d\e Y, H:i') }}
                        @if ($event->ends_at) às {{ $event->ends_at->format('H:i') }} @endif
                    </span>
                </div>
                @if ($event->location)
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-location-dot w-4" style="color: var(--brand-primary, #0C4A34);"></i>
                        <span>{{ $event->location }}</span>
                    </div>
                @endif
                @if ($event->description)
                    <p class="pt-1">{{ $event->description }}</p>
                @endif
            </div>

            @if ($event->link || $event->file_path)
                <div class="flex flex-wrap items-center gap-3 mt-4 pt-4 border-t" style="border-color: var(--border-color);">
                    @if ($event->link)
                        <a href="{{ $event->link }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 rounded-xl px-4 py-2 text-xs font-bold text-white transition hover:brightness-110" style="background-color: var(--brand-primary, #0C4A34);">
                            <i class="fa-solid fa-pen-to-square"></i> Realizar Inscrição
                        </a>
                    @endif
                    @if ($event->file_path)
                        <a href="{{ $event->fileUrl() }}" target="_blank" class="inline-flex items-center gap-2 rounded-xl px-4 py-2 text-xs font-bold transition hover:brightness-95" style="background-color: var(--bg-elevated); color: var(--text-primary);">
                            <i class="fa-solid fa-{{ $event->fileIcon() }}"></i> Baixar anexo
                        </a>
                    @endif
                </div>
            @endif
        </x-ui.modal>
    @endforeach
</div>
