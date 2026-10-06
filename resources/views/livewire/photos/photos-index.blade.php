{{--
    Tela de Fotos (listagem de álbuns).
    Lista os álbuns de fotos disponíveis com imagem de capa, cada um
    levando à tela de detalhe do álbum.
--}}
<div class="max-w-7xl mx-auto space-y-8">
    <!-- Selo da seção -->
    <div class="flex items-center">
        <div class="text-white text-xs font-extrabold uppercase px-4 py-1.5 rounded-full shadow-sm tracking-wider flex items-center gap-2" style="background-color: #0C4A34;">
            <i class="fa-solid fa-camera text-xs"></i>
            <span>FOTOS</span>
        </div>
    </div>

    @if ($albums->isEmpty())
        <!-- Estado vazio -->
        <div class="bg-white border border-slate-200 rounded-2xl p-12 text-center text-slate-500">
            Nenhum álbum disponível por enquanto.
        </div>
    @else
        <!-- Grade de álbuns -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach ($albums as $album)
                <a href="{{ route('photos.show', $album) }}" class="block rounded-2xl bg-white border border-slate-200/80 shadow-sm hover:shadow-md hover:border-emerald-500/50 transition overflow-hidden">
                    @if ($album->cover_image_path)
                        <img src="{{ \Illuminate\Support\Facades\Storage::url($album->cover_image_path) }}" alt="{{ $album->title }}" class="w-full h-40 object-cover">
                    @else
                        <div class="w-full h-40 bg-emerald-50 flex items-center justify-center">
                            <i class="fa-solid fa-image text-3xl text-emerald-200"></i>
                        </div>
                    @endif
                    <div class="p-4">
                        <span class="font-bold text-sm text-slate-800 block">{{ $album->title }}</span>
                        <span class="text-xs text-slate-500">{{ $album->event_date?->format('d/m/Y') }} · {{ $album->photos_count }} foto(s)</span>
                    </div>
                </a>
            @endforeach
        </div>
    @endif
</div>
