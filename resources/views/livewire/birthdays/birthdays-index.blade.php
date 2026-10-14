{{--
    Tela de Aniversariantes.
    Mostra o banner comemorativo do mês, cadastrado no admin.
--}}
<div class="space-y-4">
    <!-- Cabeçalho da página -->
    <x-site.page-header icon="cake-candles" title="Aniversariantes" :subtitle="'Aniversariantes de '.now()->translatedFormat('F').'.'" />

    @if ($banner)
        <div class="ui-card overflow-hidden">
            <img src="{{ $banner->imageUrl() }}" alt="Banner de {{ $banner->monthName() }}" class="w-full h-auto" />
        </div>
    @else
        <div class="ui-card p-12 text-center text-slate-500 dark:text-(--text-secondary)">
            Nenhum banner cadastrado para este mês.
        </div>
    @endif
</div>
