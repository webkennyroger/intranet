{{--
    Formulário de evento (admin), aberto em modal.
    Cadastra/edita título, descrição, local, link externo (transmissão ou
    inscrição), arquivo anexo (qualquer tipo, ex. programação em PDF),
    imagem de capa e datas de início/término.
--}}
<form wire:submit="save" class="space-y-4">
    <x-ui.input wire:model="title" label="Título" />

    <div class="space-y-1.5">
        <label class="block text-sm font-medium" style="color: var(--text-secondary);">Descrição</label>
        <textarea wire:model="description" rows="3" class="w-full rounded-xl focus:outline-none focus:ring-2 focus:ring-(--focus-ring)/40 focus:border-(--focus-ring) transition-all duration-200 px-4 py-2.5 text-sm" style="background-color: var(--bg-input); border: 1px solid var(--border-color); color: var(--text-primary);"></textarea>
    </div>

    <x-ui.input wire:model="location" label="Local" />

    <x-ui.input wire:model="link" type="url" label="Link" placeholder="https://..." hint="Transmissão ao vivo, formulário de inscrição etc." />

    <div class="grid grid-cols-2 gap-4">
        <x-ui.input wire:model="startsAt" type="datetime-local" label="Início" />
        <x-ui.input wire:model="endsAt" type="datetime-local" label="Término" />
    </div>

    <div class="space-y-1.5">
        <label class="block text-sm font-medium" style="color: var(--text-secondary);">Arquivo anexo</label>
        <div class="space-y-3">
            @if ($existingFilePath && ! $file)
                <div class="flex items-center justify-between rounded-xl px-4 py-2.5 text-sm" style="background-color: var(--bg-input); border: 1px solid var(--border-color); color: var(--text-primary);">
                    <span class="truncate"><i class="fa-solid fa-file-circle-check mr-1.5" style="color: #059669;"></i>{{ basename($existingFilePath) }}</span>
                    <button type="button" wire:click="removeFile" class="shrink-0 ml-2" style="color: var(--text-muted);">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
            @else
                <x-ui.file-upload wire:model="file" hint="Qualquer tipo de arquivo (máx. 10 MB) — PDF, Word, Excel, ZIP, vídeo, foto etc." />
                @error('file') <p class="text-xs text-red-500">{{ $message }}</p> @enderror
            @endif
        </div>
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
