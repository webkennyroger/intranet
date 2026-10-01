@props([
    'title' => null,
    'description' => null,
])

<div class="rounded-2xl p-8" style="background-color: var(--bg-card); border: 1px solid var(--border-color);">
    @if($title || $description)
        <div class="mb-6">
            @if($title)
                <h3 class="text-lg font-semibold" style="color: var(--text-primary);">{{ $title }}</h3>
            @endif
            @if($description)
                <p class="text-sm mt-1" style="color: var(--text-secondary);">{{ $description }}</p>
            @endif
        </div>
    @endif

    <form {{ $attributes->merge(['class' => 'space-y-5']) }}>
        {{ $slot }}

        @if(isset($actions))
            <div class="flex items-center gap-3 pt-4" style="border-top: 1px solid var(--border-color);">
                {{ $actions }}
            </div>
        @endif
    </form>
</div>
