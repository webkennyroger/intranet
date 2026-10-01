@props([
    'exclusive' => false,
])

<div
    x-data="{
        exclusive: @js($exclusive),
        openItems: [],
        isOpen(id) { return this.openItems.includes(id) },
        toggle(id) {
            if (this.isOpen(id)) {
                this.openItems = this.openItems.filter((item) => item !== id)
                return
            }
            this.openItems = this.exclusive ? [id] : [...this.openItems, id]
        },
    }"
    {{ $attributes->merge(['class' => 'w-full']) }}
    style="border-top: 1px solid var(--border-color);"
>
    {{ $slot }}
</div>
