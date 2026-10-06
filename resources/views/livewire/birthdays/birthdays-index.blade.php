{{--
    Tela de Aniversariantes.
    Lista os servidores que fazem aniversário no mês atual, com avatar
    e o dia do nascimento. Mostra aviso quando não há ninguém no mês.
--}}
<div class="max-w-3xl mx-auto space-y-6">
    <!-- Cabeçalho da página -->
    <div>
        <h1 class="text-3xl font-black text-slate-800 tracking-tight">Aniversariantes</h1>
        <p class="text-slate-500 text-sm mt-1 font-medium">Aniversariantes de {{ now()->translatedFormat('F') }}.</p>
    </div>

    @if ($birthdays->isEmpty())
        <!-- Estado vazio -->
        <div class="bg-white border border-slate-200 rounded-2xl p-12 text-center text-slate-500">
            Nenhum aniversariante este mês.
        </div>
    @else
        <!-- Grade de aniversariantes -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            @foreach ($birthdays as $birthdayUser)
                <div class="flex items-center gap-3 p-4 rounded-2xl bg-white border border-slate-200/80 shadow-sm">
                    <x-ui.avatar :name="$birthdayUser->name" size="lg" />
                    <div class="min-w-0">
                        <span class="font-bold text-sm text-slate-800 block">{{ $birthdayUser->name }}</span>
                        <span class="text-xs text-emerald-700 font-semibold">
                            <i class="fa-solid fa-cake-candles mr-1"></i> Dia {{ $birthdayUser->birth_date->format('d') }}
                        </span>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
