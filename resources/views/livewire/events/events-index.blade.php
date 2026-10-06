{{--
    Tela de Eventos.
    Lista os próximos eventos da Defensoria com data, título e local.
    Mostra aviso quando não há eventos programados.
--}}
<div class="max-w-7xl mx-auto space-y-8">
    <!-- Selo da seção -->
    <div class="flex items-center">
        <div class="text-white text-xs font-extrabold uppercase px-4 py-1.5 rounded-full shadow-sm tracking-wider flex items-center gap-2" style="background-color: #0C4A34;">
            <i class="fa-solid fa-calendar-days text-xs"></i>
            <span>EVENTOS</span>
        </div>
    </div>

    @if ($events->isEmpty())
        <!-- Estado vazio -->
        <div class="bg-white border border-slate-200 rounded-2xl p-12 text-center text-slate-500">
            Nenhum evento programado por enquanto.
        </div>
    @else
        <!-- Lista de eventos -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            @foreach ($events as $event)
                <div class="flex gap-4 p-4 rounded-2xl bg-white border border-slate-200/80 shadow-sm">
                    <div class="w-14 h-14 rounded-xl flex flex-col items-center justify-center shrink-0 text-white" style="background-color: #0C4A34;">
                        <span class="text-lg font-black leading-none">{{ $event->starts_at->format('d') }}</span>
                        <span class="text-[10px] uppercase font-bold">{{ $event->starts_at->translatedFormat('M') }}</span>
                    </div>
                    <div class="min-w-0">
                        <span class="font-bold text-sm text-slate-800 block">{{ $event->title }}</span>
                        <span class="text-xs text-slate-500 block mt-0.5">
                            {{ $event->starts_at->format('d/m/Y H:i') }}
                            @if ($event->location) · {{ $event->location }} @endif
                        </span>
                        @if ($event->description)
                            <span class="text-xs text-slate-500 line-clamp-2 block mt-1">{{ $event->description }}</span>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
