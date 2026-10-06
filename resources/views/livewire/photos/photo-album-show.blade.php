{{--
    Tela de um álbum de fotos específico.
    Mostra o título e a descrição do álbum e exibe todas as fotos em
    grade, abrindo a imagem original ao clicar.
--}}
<div class="max-w-7xl mx-auto space-y-6">
    <!-- Cabeçalho da página -->
    <div>
        <a href="{{ route('photos.index') }}" class="text-sm text-emerald-700 hover:underline">
            <i class="fa-solid fa-arrow-left mr-1"></i> Voltar para Fotos
        </a>
        <h1 class="text-3xl font-black text-slate-800 tracking-tight mt-2">{{ $album->title }}</h1>
        @if ($album->description)
            <p class="text-slate-500 text-sm mt-1 font-medium">{{ $album->description }}</p>
        @endif
    </div>

    @if ($photos->isEmpty())
        <!-- Estado vazio -->
        <div class="bg-white border border-slate-200 rounded-2xl p-12 text-center text-slate-500">
            Este álbum ainda não tem fotos.
        </div>
    @else
        <!-- Grade de fotos -->
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
            @foreach ($photos as $photo)
                <a href="{{ \Illuminate\Support\Facades\Storage::url($photo->image_path) }}" target="_blank" class="block rounded-xl overflow-hidden border border-slate-200/80 shadow-sm hover:shadow-md transition">
                    <img src="{{ \Illuminate\Support\Facades\Storage::url($photo->image_path) }}" alt="{{ $photo->caption }}" class="w-full h-32 object-cover">
                </a>
            @endforeach
        </div>
    @endif
</div>
