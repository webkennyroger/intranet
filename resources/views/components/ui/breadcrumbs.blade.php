@props([
    'items' => [],
])

<nav {{ $attributes->merge(['class' => 'flex']) }} aria-label="Breadcrumb">
    <ol class="flex items-center gap-1.5 flex-wrap">
        @foreach($items as $index => $item)
            @php
                $isLast = $index === count($items) - 1;
                $href = $item['href'] ?? null;
                $label = $item['label'] ?? '';
            @endphp

            <li class="flex items-center gap-1.5">
                @if($href && ! $isLast)
                    <a
                        href="{{ $href }}"
                        class="text-sm font-medium transition-colors hover:underline"
                        style="color: var(--text-secondary);"
                        onmouseover="this.style.color='var(--text-primary)'"
                        onmouseout="this.style.color='var(--text-secondary)'"
                    >
                        {{ $label }}
                    </a>
                @else
                    <span
                        class="text-sm font-semibold"
                        style="color: var(--text-primary);"
                        @if($isLast) aria-current="page" @endif
                    >
                        {{ $label }}
                    </span>
                @endif
            </li>

            @unless($isLast)
                <li class="flex items-center" aria-hidden="true">
                    <x-ui.icon name="chevron-right" class="w-4 h-4" style="color: var(--text-muted);" />
                </li>
            @endunless
        @endforeach
    </ol>
</nav>
