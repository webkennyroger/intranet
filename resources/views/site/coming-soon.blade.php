{{--
    Placeholder genérico para seções do site público ainda não
    implementadas. Recebe o título da seção via $title e só exibe um
    aviso de "em construção" dentro do layout público padrão.
--}}
<x-layouts::app :title="$title">
    <div class="max-w-7xl mx-auto">
        <div class="ui-card p-12 text-center">
            <i class="fa-solid fa-screwdriver-wrench text-4xl text-emerald-600 mb-4"></i>
            <h2 class="text-lg font-bold" style="color: var(--text-primary);">{{ $title }}</h2>
            <p class="mt-2" style="color: var(--text-secondary);">Esta seção está em construção.</p>
        </div>
    </div>
</x-layouts::app>
