{{--
    Formulário de evento (admin), aberto em modal.
    Cadastra/edita título, descrição, local, imagem de capa e datas de
    início/término.
--}}
<form wire:submit="save" class="space-y-4">
    <x-ui.input wire:model="title" label="Título" />

    <div class="space-y-1.5">
        <label class="block text-sm font-medium" style="color: var(--text-secondary);">Descrição</label>
        <textarea wire:model="description" rows="3" class="w-full rounded-xl focus:outline-none focus:ring-2 focus:ring-(--focus-ring)/40 focus:border-(--focus-ring) transition-all duration-200 px-4 py-2.5 text-sm" style="background-color: var(--bg-input); border: 1px solid var(--border-color); color: var(--text-primary);"></textarea>
    </div>

    <x-ui.input wire:model="location" label="Local" />

    <div class="grid grid-cols-2 gap-4">
        <x-ui.input wire:model="startsAt" type="datetime-local" label="Início" />
        <x-ui.input wire:model="endsAt" type="datetime-local" label="Término" />
    </div>

    <div class="space-y-1.5">
        <label class="block text-sm font-medium" style="color: var(--text-secondary);">Imagem de capa</label>
        <div class="space-y-3">
            @if ($coverImage)
                <img src="{{ $coverImage->temporaryUrl() }}" class="w-full h-32 object-cover rounded-xl" alt="Pré-visualização" />
            @elseif ($existingCoverImage)
                <img src="{{ \Illuminate\Support\Facades\Storage::url($existingCoverImage) }}" class="w-full h-32 object-cover rounded-xl" alt="Imagem atual" />
            @endif

            <x-ui.file-upload wire:model="coverImage" accept="image/*" />
            @error('coverImage') <p class="text-xs text-red-500">{{ $message }}</p> @enderror

            @if ($existingCoverImage || $coverImage)
                <button type="button" wire:click="removeCoverImage" class="text-xs font-semibold text-red-600 hover:underline">Remover imagem</button>
            @endif
        </div>
    </div>

    <x-ui.button type="submit">Salvar</x-ui.button>
</form>
