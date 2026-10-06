{{--
    Formulário de álbum de fotos (admin), aberto em modal.
    Cadastra/edita título, descrição e data do evento; permite remover
    fotos já salvas e enviar (upload) novas fotos ao álbum.
--}}
<form wire:submit="save" class="space-y-4">
    <x-ui.input wire:model="title" label="Título do álbum" />

    <div class="space-y-1.5">
        <label class="block text-sm font-medium" style="color: var(--text-secondary);">Descrição</label>
        <textarea wire:model="description" rows="2" class="w-full rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500/40 focus:border-emerald-500 transition-all duration-200 px-4 py-2.5 text-sm" style="background-color: var(--bg-input); border: 1px solid var(--border-color); color: var(--text-primary);"></textarea>
    </div>

    <x-ui.input wire:model="eventDate" type="date" label="Data do evento" />

    <!-- Fotos do Álbum (Existentes) -->
    @if ($album?->photos->isNotEmpty())
        <div class="space-y-1.5">
            <label class="block text-sm font-medium" style="color: var(--text-secondary);">Fotos do álbum</label>
            <div class="grid grid-cols-4 gap-2">
                @foreach ($album->photos as $photo)
                    <div class="relative group" wire:key="photo-{{ $photo->id }}">
                        <img src="{{ \Illuminate\Support\Facades\Storage::url($photo->image_path) }}" alt="{{ $photo->caption }}" class="w-full h-20 object-cover rounded-lg">
                        <button type="button" wire:click="removePhoto({{ $photo->id }})" wire:confirm="Remover esta foto?" class="absolute top-1 right-1 w-5 h-5 rounded-full bg-red-600 text-white text-xs flex items-center justify-center">
                            &times;
                        </button>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Adicionar Novas Fotos -->
    <div class="space-y-1.5">
        <label class="block text-sm font-medium" style="color: var(--text-secondary);">Adicionar fotos</label>
        <input type="file" wire:model="newPhotos" multiple accept="image/*" class="block w-full text-sm" style="color: var(--text-secondary);">
        <div wire:loading wire:target="newPhotos" class="text-xs" style="color: var(--text-muted);">Enviando...</div>
        @error('newPhotos.*') <p class="text-xs text-red-400">{{ $message }}</p> @enderror
    </div>

    <x-ui.checkbox wire:model="isPublished" label="Publicado" />

    <x-ui.button type="submit">Salvar</x-ui.button>
</form>
