{{--
    Placeholder genérico para seções do painel admin ainda não
    implementadas (ex.: Assistente Geral). Recebe o título via $title e
    mostra breadcrumb + aviso de "em construção" dentro do layout admin.
--}}
<x-layouts::admin :title="$title">
    <!-- Cabeçalho da página (breadcrumb) -->
    <x-admin.page-header
        :breadcrumbs="[['label' => 'Painel', 'href' => route('dashboard')], ['label' => $title]]"
        :title="$title"
    />

    <!-- Aviso de em construção -->
    <div class="ui-card p-12 text-center mt-4">
        <i class="fa-solid fa-screwdriver-wrench text-4xl text-emerald-600 mb-4"></i>
        <h2 class="text-lg font-bold text-slate-800 dark:text-(--text-primary)">{{ $title }}</h2>
        <p class="text-slate-500 dark:text-(--text-secondary) mt-2">Esta seção está em construção.</p>
    </div>
</x-layouts::admin>
