{{--
    Tela de Eventos.
    Lista os próximos eventos da Defensoria com data, título e local.
    Mostra aviso quando não há eventos programados.
--}}
<div class="space-y-4">
    <!-- Cabeçalho da página -->
    <x-site.page-header icon="calendar-days" title="Eventos" subtitle="Próximos eventos da Defensoria." />

    @if ($events->isEmpty())
        <!-- Estado vazio -->
        <div class="ui-card p-12 text-center text-slate-500 dark:text-(--text-secondary)">
            Nenhum evento programado por enquanto.
        </div>
    @else
        <!-- Lista de eventos -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            @foreach ($events as $event)
                <div class="ui-card flex gap-4 p-4">
                    <div class="w-14 h-14 rounded-xl flex flex-col items-center justify-center shrink-0 text-white" style="background-color: #0C4A34;">
                        <span class="text-lg font-black leading-none">{{ $event->starts_at->format('d') }}</span>
                        <span class="text-[10px] uppercase font-bold">{{ $event->starts_at->translatedFormat('M') }}</span>
                    </div>
                    <div class="min-w-0">
                        <span class="font-bold text-sm text-slate-800 dark:text-(--text-primary) block">{{ $event->title }}</span>
                        <span class="text-xs text-slate-500 dark:text-(--text-secondary) block mt-0.5">
                            {{ $event->starts_at->format('d/m/Y H:i') }}
                            @if ($event->location) · {{ $event->location }} @endif
                        </span>
                        @if ($event->description)
                            <span class="text-xs text-slate-500 dark:text-(--text-secondary) line-clamp-2 block mt-1">{{ $event->description }}</span>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
