{{--
    Placeholder genérico para seções do site público ainda não
    implementadas. Recebe o título da seção via $title e só exibe um
    aviso de "em construção" dentro do layout público padrão.
--}}
<x-layouts::app :title="$title">
    <div class="max-w-7xl mx-auto">
        <div class="ui-card p-12 text-center">
            <i class="fa-solid fa-screwdriver-wrench text-4xl text-emerald-600 mb-4"></i>
            <h2 class="text-lg font-bold text-slate-800 dark:text-(--text-primary)">{{ $title }}</h2>
            <p class="text-slate-500 dark:text-(--text-secondary) mt-2">Esta seção está em construção.</p>
        </div>
    </div>
</x-layouts::app>
