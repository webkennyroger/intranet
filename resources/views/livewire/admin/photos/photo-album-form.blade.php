{{--
    Formulário de álbum de fotos (admin), aberto em modal.
    Cadastra/edita título, descrição e data do evento; permite remover
    fotos já salvas e enviar (upload) novas fotos ao álbum.
--}}
<form wire:submit="save" class="space-y-4">
    <x-ui.input wire:model="title" label="Título do álbum" />

    <x-ui.textarea wire:model="description" label="Descrição" rows="2" />

    <x-ui.input wire:model="eventDate" type="date" label="Data do evento" />

    <!-- Fotos do Álbum (Existentes) -->
    @if ($album?->photos->isNotEmpty())
        <div class="space-y-1.5">
            <div class="flex items-center justify-between">
                <label class="block text-sm font-medium" style="color: var(--text-secondary);">Fotos do álbum</label>
                <div class="flex items-center gap-3">
                    @if (! empty($selectedPhotoIds))
                        <span class="text-xs font-semibold" style="color: var(--text-muted);">{{ count($selectedPhotoIds) }} selecionada(s)</span>
                        <button type="button" @click="confirmAction('Confirma a exclusão de {{ count($selectedPhotoIds) }} foto(s) selecionada(s)?', () => $wire.removeSelectedPhotos())" class="text-xs font-semibold text-red-500 hover:underline">
                            Excluir selecionadas
                        </button>
                    @endif
                    <button type="button" wire:click="toggleSelectAllPhotos" class="text-xs font-semibold hover:underline" style="color: var(--admin-primary);">
                        {{ count($selectedPhotoIds) === $album->photos->count() ? 'Desmarcar todas' : 'Selecionar todas' }}
                    </button>
                </div>
            </div>
            <div class="grid grid-cols-4 gap-2">
                @foreach ($album->photos as $photo)
                    <div class="relative group" wire:key="photo-{{ $photo->id }}">
                        <img src="{{ \Illuminate\Support\Facades\Storage::url($photo->image_path) }}" alt="{{ $photo->caption }}" class="w-full h-20 object-cover rounded-lg">
                        <div class="absolute top-1 left-1 rounded-md bg-white p-0.5 shadow-sm">
                            <x-ui.checkbox wire:model.live="selectedPhotoIds" value="{{ $photo->id }}" />
                        </div>
                        <div class="absolute top-1 right-1">
                            <x-ui.tooltip text="Remover foto">
                                <button type="button" @click="confirmAction('Remover esta foto?', () => $wire.removePhoto({{ $photo->id }}))" class="w-5 h-5 rounded-full bg-red-600 text-white text-xs flex items-center justify-center">
                                    &times;
                                </button>
                            </x-ui.tooltip>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Adicionar Novas Fotos -->
    <div class="space-y-1.5">
        <x-ui.file-upload wire:model="newPhotos" multiple accept="image/*" label="Adicionar fotos" hint="PNG, JPG ou GIF (máx. 4 MB cada). Pode selecionar ou arrastar várias de uma vez." />
        @error('newPhotos.*') <p class="text-xs text-red-400">{{ $message }}</p> @enderror

        @if (! empty($newPhotos))
            <div class="grid grid-cols-4 gap-2">
                @foreach ($newPhotos as $index => $photo)
                    <div class="relative group" wire:key="new-photo-{{ $index }}">
                        <img src="{{ $photo->temporaryUrl() }}" alt="Pré-visualização" class="w-full h-20 object-cover rounded-lg">
                        <div class="absolute top-1 right-1">
                            <x-ui.tooltip text="Remover">
                                <button type="button" wire:click="removeNewPhoto({{ $index }})" class="w-5 h-5 rounded-full bg-red-600 text-white text-xs flex items-center justify-center">
                                    &times;
                                </button>
                            </x-ui.tooltip>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <x-ui.button type="submit" class="w-full justify-center">
        <i class="fa-solid fa-floppy-disk mr-1.5"></i> Salvar
    </x-ui.button>
</form>
