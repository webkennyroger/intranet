{{--
    Tela de um álbum de fotos específico.
    Mostra o título e a descrição do álbum e exibe todas as fotos em
    grade, abrindo a imagem original no lightbox (x-ui.lightbox) ao clicar
    — com setas pra navegar entre todas as fotos do álbum, não só a clicada.
--}}
@php
    $lightboxItems = $photos->map(fn ($photo) => [
        'url' => \Illuminate\Support\Facades\Storage::url($photo->image_path),
        'name' => $photo->caption,
    ])->values();
@endphp
<div class="space-y-4">
    <!-- Cabeçalho da página -->
    <x-site.page-header icon="camera" :title="$album->title" :subtitle="$album->description" :back="route('photos.index')" />

    @if ($photos->isEmpty())
        <!-- Estado vazio -->
        <div class="ui-card p-12 text-center text-slate-500 dark:text-(--text-secondary)">
            Este álbum ainda não tem fotos.
        </div>
    @else
        <!-- Grade de fotos -->
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
            @foreach ($photos as $index => $photo)
                <button
                    type="button"
                    @click="window.dispatchEvent(new CustomEvent('open-lightbox', { detail: { items: @js($lightboxItems), index: {{ $index }} } }))"
                    class="ui-card overflow-hidden hover:shadow-md transition group cursor-pointer"
                >
                    <img src="{{ \Illuminate\Support\Facades\Storage::url($photo->image_path) }}" alt="{{ $photo->caption }}" class="w-full h-32 object-cover group-hover:scale-105 transition duration-500">
                </button>
            @endforeach
        </div>
    @endif

    <x-ui.lightbox />
</div>
