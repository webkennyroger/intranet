{{--
    Formulário de banner de Aniversariantes (admin), aberto em modal.
    Cadastra/edita o mês e a imagem/arte comemorativa daquele mês — no
    máximo um banner por mês.
--}}
<form wire:submit="save" class="space-y-5">
    <x-ui.select wire:model="month" label="Mês" placeholder="Selecione o mês" :error="$errors->first('month')">
        @foreach ($monthNames as $value => $label)
            <option value="{{ $value }}">{{ $label }}</option>
        @endforeach
    </x-ui.select>

    <!-- Imagem -->
    <div class="pt-4" style="border-top: 1px solid var(--border-color);">
        <p class="text-[11px] font-bold uppercase tracking-wide mb-3" style="color: var(--admin-primary, var(--brand-primary, #0C4A34));">
            <i class="fa-solid fa-image mr-1.5"></i>Imagem
        </p>

        <div class="space-y-3">
            @if ($image)
                <img src="{{ $image->temporaryUrl() }}" class="w-full h-32 object-cover rounded-xl" alt="Pré-visualização" />
            @elseif ($existingImage)
                <img src="{{ \Illuminate\Support\Facades\Storage::url($existingImage) }}" class="w-full h-32 object-cover rounded-xl" alt="Imagem atual" />
            @endif

            <x-ui.file-upload wire:model="image" accept="image/*" hint="PNG, JPG ou GIF (máx. 4 MB)" />
            @error('image') <p class="text-xs text-red-500">{{ $message }}</p> @enderror
        </div>
    </div>

    <x-ui.button type="submit" class="w-full justify-center">
        <i class="fa-solid fa-floppy-disk mr-1.5"></i> Salvar
    </x-ui.button>
</form>
