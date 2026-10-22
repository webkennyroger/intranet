{{--
    Visão Mês: grade de semanas completas (domingo a sábado) a partir de
    $this->monthWeeks(). Célula vazia clicada cria um evento na data;
    soltar um chip arrastado da barra lateral faz o mesmo via quickCreate;
    arrastar um evento já existente pra outro dia reagenda via
    rescheduleEvent (o payload tem "type" pra diferenciar os dois casos).
--}}
<div>
    <div class="grid grid-cols-7 text-center text-[11px] font-bold uppercase tracking-wide py-2" style="color: var(--cal-text-muted);">
        @foreach (['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'] as $label)
            <span>{{ $label }}</span>
        @endforeach
    </div>

    <div class="grid grid-cols-7" style="border-top: 1px solid var(--cal-border); border-left: 1px solid var(--cal-border);">
        @foreach ($this->monthWeeks as $week)
            @foreach ($week as $day)
                <div
                    wire:key="month-day-{{ $day['date']->format('Y-m-d') }}"
                    x-on:dragover.prevent
                    x-on:drop="let data = JSON.parse($event.dataTransfer.getData('application/json')); data.type === 'event' ? $wire.rescheduleEvent(data.eventId, '{{ $day['date']->format('Y-m-d') }}') : $wire.quickCreate(data.categoryId, data.label, '{{ $day['date']->format('Y-m-d') }}')"
                    x-on:click="$wire.createOnDate('{{ $day['date']->format('Y-m-d') }}')"
                    class="min-h-24 p-1.5 cursor-pointer transition hover:brightness-[0.98]"
                    style="border-right: 1px solid var(--cal-border); border-bottom: 1px solid var(--cal-border); {{ $day['inMonth'] ? '' : 'background-color: var(--cal-bg-soft);' }}"
                >
                    <span
                        class="inline-flex items-center justify-center w-6 h-6 rounded-full text-xs font-bold"
                        style="{{ $day['isToday'] ? 'background-color: var(--cal-primary); color: #fff;' : ($day['inMonth'] ? 'color: var(--cal-text);' : 'color: var(--cal-text-muted);') }}"
                    >{{ $day['date']->day }}</span>

                    <div class="mt-1 space-y-0.5">
                        @foreach ($day['events']->take(2) as $event)
                            <button
                                type="button"
                                draggable="true"
                                x-on:dragstart.stop="$event.dataTransfer.setData('application/json', JSON.stringify({{ Illuminate\Support\Js::from(['type' => 'event', 'eventId' => $event->id]) }}))"
                                x-on:click.stop="$wire.viewEvent({{ $event->id }}); window.dispatchEvent(new CustomEvent('open-modal', { detail: 'event-view' }))"
                                class="block w-full truncate rounded px-1.5 py-0.5 text-[11px] font-semibold text-left cursor-grab"
                                style="background-color: color-mix(in srgb, {{ $event->colorHex() }} 15%, white); color: {{ $event->colorHex() }};"
                            >{{ $event->title }}</button>
                        @endforeach

                        @if ($day['events']->count() > 2)
                            <span class="block text-[10px] font-semibold px-1.5" style="color: var(--cal-text-muted);">+{{ $day['events']->count() - 2 }} mais</span>
                        @endif
                    </div>
                </div>
            @endforeach
        @endforeach
    </div>
</div>
