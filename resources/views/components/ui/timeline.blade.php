@props([
    'items' => [],
])

<ol {{ $attributes->merge(['class' => 'relative']) }}>
    @foreach($items as $item)
        <li class="relative pb-8 pl-8 last:pb-0">
            @unless($loop->last)
                <span
                    class="absolute top-4 h-full w-px"
                    style="left: 5px; background-color: var(--border-color);"
                ></span>
            @endunless

            <span
                class="absolute top-1 h-3 w-3 rounded-full bg-emerald-500"
                style="left: 0; box-shadow: 0 0 0 3px var(--bg-card);"
            ></span>

            <div class="flex flex-col gap-0.5">
                @if(!empty($item['date']))
                    <time class="text-xs font-medium" style="color: var(--text-muted);">{{ $item['date'] }}</time>
                @endif
                @if(!empty($item['title']))
                    <p class="text-sm font-semibold" style="color: var(--text-primary);">{{ $item['title'] }}</p>
                @endif
                @if(!empty($item['description']))
                    <p class="text-sm" style="color: var(--text-secondary);">{{ $item['description'] }}</p>
                @endif
            </div>
        </li>
    @endforeach
</ol>
