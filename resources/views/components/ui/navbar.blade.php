@props([
    'items' => [],
])

<nav
    {{ $attributes->merge(['class' => 'h-16 backdrop-blur-md px-4 md:px-8 flex items-center justify-between gap-4 border-b']) }}
    style="border-color: var(--border-color); background-color: var(--bg-surface);"
>
    <div class="flex items-center gap-8 min-w-0">
        <div class="flex-shrink-0">
            @if(isset($brand))
                {{ $brand }}
            @else
                <x-ui.brand size="sm" />
            @endif
        </div>

        @if(count($items))
            <div class="flex items-center gap-1 overflow-x-auto">
                @foreach($items as $item)
                    @php $isActive = $item['active'] ?? false; @endphp
                    <a
                        href="{{ $item['href'] ?? '#' }}"
                        class="px-3 py-2 rounded-lg text-sm font-medium whitespace-nowrap transition-colors duration-200 {{ $isActive ? 'bg-emerald-500/10 text-emerald-400' : 'hover:bg-[var(--bg-hover)]' }}"
                        style="{{ $isActive ? '' : 'color: var(--text-secondary);' }}"
                        @if($isActive) aria-current="page" @endif
                    >
                        {{ $item['label'] ?? '' }}
                    </a>
                @endforeach
            </div>
        @endif
    </div>

    @if(isset($actions))
        <div class="flex items-center gap-2 flex-shrink-0">
            {{ $actions }}
        </div>
    @endif
</nav>
