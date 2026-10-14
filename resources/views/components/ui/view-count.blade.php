@props(['count'])

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5']) }}>
    <x-ui.icon name="eye" class="size-4" />
    <span>{{ $count }}</span>
</span>
