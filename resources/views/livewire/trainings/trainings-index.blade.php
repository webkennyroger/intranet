{{--
    Tela de Capacitações.
    Lista os cursos e treinamentos disponíveis, cada um com título,
    descrição e link externo de inscrição/acesso.
--}}
<div class="space-y-4">
    <!-- Cabeçalho da página -->
    <x-site.page-header icon="graduation-cap" title="Capacitações" subtitle="Cursos e treinamentos disponíveis." />

    @if ($trainings->isEmpty())
        <!-- Estado vazio -->
        <div class="ui-card p-12 text-center text-slate-500 dark:text-(--text-secondary)">
            Nenhuma capacitação disponível por enquanto.
        </div>
    @else
        <!-- Grade de capacitações -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            @foreach ($trainings as $training)
                <a href="{{ $training->external_url }}" target="_blank" rel="noopener" class="ui-card flex items-start gap-3 p-4 hover:shadow-md transition">
                    <div class="w-11 h-11 rounded-xl bg-emerald-50 flex items-center justify-center shrink-0">
                        <i class="fa-solid fa-graduation-cap text-lg text-emerald-800"></i>
                    </div>
                    <div class="min-w-0">
                        <span class="font-bold text-sm text-slate-800 dark:text-(--text-primary) leading-snug block">{{ $training->title }}</span>
                        @if ($training->description)
                            <span class="text-xs text-slate-500 dark:text-(--text-secondary) line-clamp-2 block mt-0.5">{{ $training->description }}</span>
                        @endif
                        <span class="text-xs text-emerald-700 font-semibold block mt-1">
                            @if ($training->workload_hours) {{ $training->workload_hours }}h @endif
                            @if ($training->starts_at) · {{ $training->starts_at->format('d/m/Y') }} @endif
                        </span>
                    </div>
                </a>
            @endforeach
        </div>
    @endif
</div>
