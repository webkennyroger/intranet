{{--
    Formulário de banner de Aniversariantes (admin), aberto em modal.
    Cadastra/edita o mês e a imagem/arte comemorativa daquele mês — no
    máximo um banner por mês.
--}}
<form wire:submit="save" class="space-y-4">
    <x-ui.select wire:model="month" label="Mês" placeholder="Selecione o mês" :error="$errors->first('month')">
        @foreach ($monthNames as $value => $label)
            <option value="{{ $value }}">{{ $label }}</option>
        @endforeach
    </x-ui.select>

    <div class="space-y-1.5">
        <label class="block text-sm font-medium" style="color: var(--text-secondary);">Imagem</label>
        <div class="space-y-3">
            @if ($image)
                <img src="{{ $image->temporaryUrl() }}" class="w-full h-32 object-cover rounded-xl" alt="Pré-visualização" />
            @elseif ($existingImage)
                <img src="{{ \Illuminate\Support\Facades\Storage::url($existingImage) }}" class="w-full h-32 object-cover rounded-xl" alt="Imagem atual" />
            @endif

            <x-ui.file-upload wire:model="image" accept="image/*" />
            @error('image') <p class="text-xs text-red-500">{{ $message }}</p> @enderror
        </div>
    </div>

    <x-ui.button type="submit">Salvar</x-ui.button>
</form>
