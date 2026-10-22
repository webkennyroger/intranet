{{--
    Visão Lista: eventos do mês do cursor atual, agrupados por data.
    Linhas cheias (sem cantos, sem espaçamento entre elas) com o fundo e o
    texto na cor da categoria — mesmo estilo "banded list" da referência.
--}}
<div>
    @forelse ($this->listGroups as $dateKey => $dayEvents)
        @php $date = \Carbon\Carbon::parse($dateKey); @endphp
        <div wire:key="list-day-{{ $dateKey }}">
            <div class="flex items-baseline justify-between px-1 py-2.5 border-b" style="border-color: var(--cal-border);">
                <p class="text-sm font-bold" style="color: var(--cal-text);">{{ $date->translatedFormat('d \d\e F \d\e Y') }}</p>
                <p class="text-xs font-semibold" style="color: var(--cal-text-muted);">{{ ucfirst($date->translatedFormat('l')) }}</p>
            </div>

            @foreach ($dayEvents as $event)
                <button
                    type="button"
                    x-on:click="$wire.viewEvent({{ $event->id }}); window.dispatchEvent(new CustomEvent('open-modal', { detail: 'event-view' }))"
                    class="w-full flex items-center gap-3 px-4 py-2.5 text-left transition hover:brightness-95 border-b"
                    style="background-color: color-mix(in srgb, {{ $event->colorHex() }} 14%, white); border-color: var(--cal-border);"
                >
                    <span class="text-xs font-bold shrink-0 w-14" style="color: {{ $event->colorHex() }};">{{ $event->starts_at->format('H:i') }}</span>
                    <span class="w-2 h-2 rounded-full shrink-0" style="background-color: {{ $event->colorHex() }};"></span>
                    <span class="text-sm font-bold truncate" style="color: {{ $event->colorHex() }};">{{ $event->title }}</span>
                </button>
            @endforeach
        </div>
    @empty
        <p class="text-sm py-6 text-center" style="color: var(--cal-text-muted);">Nenhum evento neste período.</p>
    @endforelse
</div>
