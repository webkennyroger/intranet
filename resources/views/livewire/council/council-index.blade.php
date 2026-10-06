{{--
    Tela do Conselho Superior.
    Lista os membros do conselho com foto, nome e cargo em formato
    de grade. Mostra aviso quando ainda não há membros cadastrados.
--}}
<div class="max-w-7xl mx-auto space-y-8">
    <!-- Selo da seção -->
    <div class="flex items-center">
        <div class="text-white text-xs font-extrabold uppercase px-4 py-1.5 rounded-full shadow-sm tracking-wider flex items-center gap-2" style="background-color: #0C4A34;">
            <i class="fa-solid fa-scale-balanced text-xs"></i>
            <span>CONSELHO SUPERIOR</span>
        </div>
    </div>

    @if ($members->isEmpty())
        <!-- Estado vazio -->
        <div class="bg-white border border-slate-200 rounded-2xl p-12 text-center text-slate-500">
            Nenhum membro cadastrado por enquanto.
        </div>
    @else
        <!-- Grade de membros do conselho -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            @foreach ($members as $member)
                <div class="flex flex-col items-center text-center gap-3 p-5 rounded-2xl bg-white border border-slate-200/80 shadow-sm">
                    <x-ui.avatar :src="$member->photo_path ? \Illuminate\Support\Facades\Storage::url($member->photo_path) : null" :name="$member->name" size="xl" />
                    <div>
                        <span class="font-bold text-sm text-slate-800 block">{{ $member->name }}</span>
                        <span class="text-xs text-slate-500">{{ $member->role }}</span>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
