{{--
    Tela do Conselho Superior.
    Lista os membros do conselho com foto, nome e cargo em formato
    de grade. Mostra aviso quando ainda não há membros cadastrados.
--}}
<div class="space-y-4">
    <!-- Cabeçalho da página -->
    <x-site.page-header icon="scale-balanced" title="Conselho Superior" subtitle="Membros do Conselho Superior da Defensoria." />

    @if ($members->isEmpty())
        <!-- Estado vazio -->
        <div class="ui-card p-12 text-center text-slate-500 dark:text-(--text-secondary)">
            Nenhum membro cadastrado por enquanto.
        </div>
    @else
        <!-- Grade de membros do conselho -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            @foreach ($members as $member)
                <div class="ui-card flex flex-col items-center text-center gap-3 p-5">
                    <x-ui.avatar :src="$member->photo_path ? \Illuminate\Support\Facades\Storage::url($member->photo_path) : null" :name="$member->name" size="xl" />
                    <div>
                        <span class="font-bold text-sm text-slate-800 dark:text-(--text-primary) block">{{ $member->name }}</span>
                        <span class="text-xs text-slate-500 dark:text-(--text-secondary)">{{ $member->role }}</span>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
