{{--
    Visão Dia: mesma grade de horas (0h-23h) da visão Semana, só com
    uma coluna (o dia do cursor atual).
--}}
<div class="overflow-y-auto" style="max-height: 34rem;" x-init="$el.scrollTop = 320">
    <div class="grid" style="grid-template-columns: 56px 1fr;">
        @foreach (range(0, 23) as $hour)
            @php $hourEvents = $this->dayEvents->filter(fn ($event) => (int) $event->starts_at->format('G') === $hour); @endphp

            <div class="text-[10px] text-right pr-2 py-3" style="border-bottom: 1px solid var(--cal-border); color: var(--cal-text-muted);">{{ $hour }}h</div>

            <div
                wire:key="day-hour-{{ $hour }}"
                x-on:dragover.prevent
                x-on:drop="let data = JSON.parse($event.dataTransfer.getData('application/json')); $wire.quickCreate(data.categoryId, data.label, '{{ $this->cursor }}')"
                class="px-2 py-1"
                style="border-bottom: 1px solid var(--cal-border); border-left: 1px solid var(--cal-border); min-height: 2.5rem;"
            >
                @foreach ($hourEvents as $event)
                    <button
                        type="button"
                        x-on:click="$wire.viewEvent({{ $event->id }}); window.dispatchEvent(new CustomEvent('open-modal', { detail: 'event-view' }))"
                        class="block w-full truncate rounded px-2 py-1 text-xs font-semibold text-left mb-1"
                        style="background-color: color-mix(in srgb, {{ $event->colorHex() }} 18%, white); color: {{ $event->colorHex() }};"
                    >{{ $event->starts_at->format('H:i') }} — {{ $event->title }}</button>
                @endforeach
            </div>
        @endforeach
    </div>
</div>
