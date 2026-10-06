{{--
    Botão que alterna entre tema claro/escuro, usado no header do layout admin.
    Lê a preferência salva em localStorage (ou o tema do sistema, se nunca
    escolheu) e aplica/remove a classe "dark" na tag <html>.
--}}
@props([
    'label' => 'Alternar tema',
])

<button
    x-data="{ dark: localStorage.getItem('theme') === 'dark' || (!localStorage.getItem('theme') && window.matchMedia('(prefers-color-scheme: dark)').matches) }"
    x-init="
        document.documentElement.classList.toggle('dark', dark);
        $watch('dark', val => {
            localStorage.setItem('theme', val ? 'dark' : 'light');
            document.documentElement.classList.add('theme-transition');
            document.documentElement.classList.toggle('dark', val);
            setTimeout(() => document.documentElement.classList.remove('theme-transition'), 300);
        });
    "
    @click="dark = !dark"
    {{ $attributes->merge(['class' => 'p-2 rounded-xl transition-colors text-(--text-secondary) hover:text-amber-400']) }}
    title="{{ $label }}"
>
    <x-ui.icon name="moon" class="w-5 h-5" x-show="dark" />
    <x-ui.icon name="sun" class="w-5 h-5" x-show="!dark" />
</button>
