{{--
    Formulário de membro do Conselho Superior (admin), aberto em modal.
    Cadastra/edita nome, cargo, foto (upload) e se o membro está ativo
    (visível na página pública do Conselho).
--}}
<form wire:submit="save" class="space-y-4">
    <x-ui.input wire:model="name" label="Nome" />
    <x-ui.input wire:model="role" label="Cargo" />

    <!-- Upload de Foto -->
    <div class="space-y-1.5">
        <label class="block text-sm font-medium" style="color: var(--text-secondary);">Foto</label>
        @if ($member?->photo_path)
            <img src="{{ \Illuminate\Support\Facades\Storage::url($member->photo_path) }}" alt="{{ $member->name }}" class="w-16 h-16 rounded-full object-cover mb-2">
        @endif
        <input type="file" wire:model="photo" accept="image/*" class="block w-full text-sm" style="color: var(--text-secondary);">
        <x-ui.upload-progress target="photo" class="mt-2" />
        @error('photo') <p class="text-xs text-red-400">{{ $message }}</p> @enderror
    </div>

    <x-ui.checkbox wire:model="isActive" label="Ativo" />

    <x-ui.button type="submit">Salvar</x-ui.button>
</form>
