{{--
    Página inicial do site exibida ao usuário logado, com uma saudação
    simples usando o nome do usuário autenticado.
--}}
<x-layouts::app title="Início">
    <div class="max-w-7xl mx-auto">
        <h2 class="text-xl font-bold text-slate-800">Início</h2>
        <p class="text-slate-500 mt-1">Bem-vindo à intranet, {{ auth()->user()->name }}.</p>
    </div>
</x-layouts::app>

