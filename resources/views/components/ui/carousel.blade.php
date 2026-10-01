@props([
    'items' => [],
    'autoplay' => false,
    'interval' => 5000,
    'showControls' => true,
])

@php
    $intervalMs = max(1000, (int) $interval);
@endphp

<div
    x-data="{
        active: 0,
        count: 0,
        timer: null,
        init() {
            this.count = this.$refs.track.children.length
            this.update()
            this.play()
        },
        play() {
            @if($autoplay)
            this.stop()
            this.timer = setInterval(() => this.next(), {{ $intervalMs }})
            @endif
        },
        stop() {
            if (this.timer) {
                clearInterval(this.timer)
                this.timer = null
            }
        },
        update() {
            Array.from(this.$refs.track.children).forEach((el, i) => {
                el.classList.toggle('hidden', i !== this.active)
            })
        },
        next() {
            if (this.count < 2) return
            this.active = (this.active + 1) % this.count
            this.update()
        },
        prev() {
            if (this.count < 2) return
            this.active = (this.active - 1 + this.count) % this.count
            this.update()
        },
        goto(i) {
            this.active = i
            this.update()
        },
    }"
    @mouseenter="stop()"
    @mouseleave="play()"
    {{ $attributes->merge(['class' => 'relative']) }}
>
    <div class="overflow-hidden rounded-xl" style="border: 1px solid var(--border-color);">
        <div x-ref="track">
            @if(count($items))
                @foreach($items as $item)
                    <div>
                        @if(!empty($item['image']))
                            <img src="{{ $item['image'] }}" alt="{{ $item['alt'] ?? $item['caption'] ?? '' }}" class="w-full h-auto">
                        @endif
                        @if(!empty($item['caption']))
                            <div class="px-4 py-2 text-center text-sm" style="color: var(--text-secondary);">
                                {{ $item['caption'] }}
                            </div>
                        @endif
                    </div>
                @endforeach
            @else
                {{ $slot }}
            @endif
        </div>
    </div>

    @if($showControls === true)
    <template x-if="count > 1">
        <button
            type="button"
            @click="prev()"
            aria-label="Anterior"
            class="absolute left-2 top-1/2 -translate-y-1/2 rounded-full p-2 shadow-lg transition-opacity hover:opacity-80"
            style="background-color: var(--bg-elevated); color: var(--text-primary); border: 1px solid var(--border-color);"
        >
            <x-ui.icon name="chevron-left" class="w-4 h-4" />
        </button>
    </template>

    <template x-if="count > 1">
        <button
            type="button"
            @click="next()"
            aria-label="Próximo"
            class="absolute right-2 top-1/2 -translate-y-1/2 rounded-full p-2 shadow-lg transition-opacity hover:opacity-80"
            style="background-color: var(--bg-elevated); color: var(--text-primary); border: 1px solid var(--border-color);"
        >
            <x-ui.icon name="chevron-right" class="w-4 h-4" />
        </button>
    </template>
    @endif

    @if($showControls !== false)
    <template x-if="count > 1">
        <div class="mt-3 flex justify-center gap-2">
            <template x-for="i in count" :key="i">
                <button
                    type="button"
                    @click="goto(i - 1)"
                    aria-label="Ir para o slide"
                    class="h-2 w-2 rounded-full transition-all duration-200"
                    :class="active === (i - 1) ? 'w-4 bg-emerald-500' : ''"
                    :style="active === (i - 1) ? '' : 'background-color: var(--border-color);'"
                ></button>
            </template>
        </div>
    </template>
    @endif
</div>
