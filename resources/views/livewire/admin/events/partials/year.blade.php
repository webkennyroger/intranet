{{--
    Visão Ano: 12 mini-grades compactas (uma por mês), só com número do
    dia e uma bolinha indicando que há evento — clicar num dia navega
    pra visão Mês naquele mês (goToMonth), sem arrastar-e-soltar aqui
    (grade pequena demais pra isso ser prático).
--}}
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
    @foreach ($this->yearMonths as $month)
        <div class="rounded-xl p-3" style="background-color: var(--cal-bg-soft);">
            <p class="font-bold text-sm mb-2 text-center" style="color: var(--cal-text);">{{ ucfirst($month['label']) }}</p>

            <div class="grid grid-cols-7 gap-y-0.5 text-center text-[10px] font-semibold" style="color: var(--cal-text-muted);">
                @foreach (['D', 'S', 'T', 'Q', 'Q', 'S', 'S'] as $label)
                    <span>{{ $label }}</span>
                @endforeach

                @foreach ($month['weeks'] as $week)
                    @foreach ($week as $day)
                        <button
                            type="button"
                            wire:click="goToMonth('{{ $day['date']->format('Y-m-d') }}')"
                            wire:key="year-day-{{ $day['date']->format('Y-m-d') }}"
                            class="relative w-6 h-6 rounded-full flex items-center justify-center mx-auto"
                            style="{{ $day['isToday'] ? 'background-color: var(--cal-primary); color: #fff;' : ($day['inMonth'] ? 'color: var(--cal-text);' : 'color: var(--cal-text-muted); opacity: .4;') }}"
                        >
                            {{ $day['date']->day }}
                            @if ($day['events']->isNotEmpty())
                                <span class="absolute bottom-0 right-0 w-1.5 h-1.5 rounded-full" style="background-color: {{ $day['events']->first()->colorHex() }};"></span>
                            @endif
                        </button>
                    @endforeach
                @endforeach
            </div>
        </div>
    @endforeach
</div>
