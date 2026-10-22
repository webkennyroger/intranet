{{--
    Visão Semana: 7 colunas (dom-sáb) x linhas de hora (0h-23h), cada
    evento aparece na linha da sua hora de início. Sem posicionamento
    por minuto/pixel (trade-off deliberado pra não depender de JS extra).
--}}
<div class="overflow-y-auto" style="max-height: 34rem;" x-init="$el.scrollTop = 320">
    <div class="grid" style="grid-template-columns: 56px repeat(7, 1fr);">
        <div></div>
        @foreach ($this->weekDays as $day)
            <div
                class="text-center text-xs font-bold py-2"
                style="border-bottom: 1px solid var(--cal-border); color: {{ $day['isToday'] ? 'var(--cal-primary)' : 'var(--cal-text)' }};"
            >{{ ucfirst($day['date']->translatedFormat('D')) }}, {{ $day['date']->format('d/m') }}</div>
        @endforeach

        @foreach (range(0, 23) as $hour)
            <div class="text-[10px] text-right pr-2 py-3" style="border-bottom: 1px solid var(--cal-border); color: var(--cal-text-muted);">{{ $hour }}h</div>

            @foreach ($this->weekDays as $day)
                @php $hourEvents = $day['events']->filter(fn ($event) => (int) $event->starts_at->format('G') === $hour); @endphp
                <div
                    wire:key="week-{{ $day['date']->format('Y-m-d') }}-{{ $hour }}"
                    x-on:dragover.prevent
                    x-on:drop="let data = JSON.parse($event.dataTransfer.getData('application/json')); data.type === 'event' ? $wire.rescheduleEvent(data.eventId, '{{ $day['date']->format('Y-m-d') }}') : $wire.quickCreate(data.categoryId, data.label, '{{ $day['date']->format('Y-m-d') }}')"
                    class="px-1 py-1"
                    style="border-bottom: 1px solid var(--cal-border); border-left: 1px solid var(--cal-border); min-height: 2.5rem;"
                >
                    @foreach ($hourEvents as $event)
                        <button
                            type="button"
                            draggable="true"
                            x-on:dragstart.stop="$event.dataTransfer.setData('application/json', JSON.stringify({{ Illuminate\Support\Js::from(['type' => 'event', 'eventId' => $event->id]) }}))"
                            x-on:click="$wire.viewEvent({{ $event->id }}); window.dispatchEvent(new CustomEvent('open-modal', { detail: 'event-view' }))"
                            class="block w-full truncate rounded px-1 text-[10px] font-semibold text-left mb-0.5 cursor-grab"
                            style="background-color: color-mix(in srgb, {{ $event->colorHex() }} 18%, white); color: {{ $event->colorHex() }};"
                        >{{ $event->starts_at->format('H:i') }} {{ $event->title }}</button>
                    @endforeach
                </div>
            @endforeach
        @endforeach
    </div>
</div>
