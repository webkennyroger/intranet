@props([
    'label' => 'Alternar tema',
])

<button
    x-data="{ light: localStorage.getItem('theme') === 'light' }"
    x-init="
        if (!localStorage.getItem('theme')) {
            light = window.matchMedia('(prefers-color-scheme: light)').matches;
        }
        document.documentElement.classList.toggle('light', light);
        $watch('light', val => {
            localStorage.setItem('theme', val ? 'light' : 'dark');
            document.documentElement.classList.add('theme-transition');
            document.documentElement.classList.toggle('light', val);
            setTimeout(() => document.documentElement.classList.remove('theme-transition'), 300);
        });
    "
    @click="light = !light"
    {{ $attributes->merge(['class' => 'p-2 rounded-xl transition-colors text-(--text-secondary) hover:text-amber-400']) }}
    title="{{ $label }}"
>
    <x-ui.icon name="sun" class="w-5 h-5" x-show="light" />
    <x-ui.icon name="moon" class="w-5 h-5" x-show="!light" />
</button>
