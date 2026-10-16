{{--
    Formulário de capacitação/curso (admin), aberto em modal.
    Cadastra/edita título, descrição, um ou mais links de acesso/inscrição
    (um curso pode ter várias etapas/materiais, cada um com seu próprio
    link), carga horária, data de início e se está publicado.
--}}
<form wire:submit="save" class="space-y-4" x-on:keydown.enter="if ($event.target.tagName === 'INPUT') { $event.preventDefault(); }">
    <x-ui.input wire:model="title" label="Título" />

    <div class="space-y-1.5">
        <label class="block text-sm font-medium" style="color: var(--text-secondary);">Descrição</label>
        <textarea wire:model="description" rows="3" class="w-full rounded-xl focus:outline-none focus:ring-2 focus:ring-(--focus-ring)/40 focus:border-(--focus-ring) transition-all duration-200 px-4 py-2.5 text-sm" style="background-color: var(--bg-input); border: 1px solid var(--border-color); color: var(--text-primary);"></textarea>
    </div>

    <div class="space-y-2">
        <label class="block text-sm font-medium" style="color: var(--text-secondary);">Links do curso</label>

        @foreach ($links as $index => $link)
            <div class="flex items-start gap-2" wire:key="training-link-{{ $index }}">
                <div class="w-32 shrink-0">
                    <x-ui.input wire:model="links.{{ $index }}.label" placeholder="Rótulo (opcional)" />
                </div>
                <div class="flex-1">
                    <x-ui.input wire:model="links.{{ $index }}.url" type="url" placeholder="https://..." :error="$errors->first('links.'.$index.'.url')" />
                </div>
                @if (count($links) > 1)
                    <x-ui.tooltip text="Remover link" class="mt-2.5 shrink-0">
                        <button type="button" wire:click="removeLink({{ $index }})" class="text-red-600 hover:text-red-700">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </x-ui.tooltip>
                @endif
            </div>
        @endforeach

        @error('links') <p class="text-xs text-red-400">{{ $message }}</p> @enderror

        <button type="button" wire:click="addLink" class="inline-flex items-center gap-1.5 text-xs font-semibold" style="color: var(--brand-primary, #0C4A34);">
            <i class="fa-solid fa-plus"></i> Adicionar link
        </button>

        <p class="text-xs" style="color: var(--text-muted);">Onde o conteúdo (vídeos, material) está hospedado. Use mais de um link quando o curso tiver várias etapas ou materiais.</p>
    </div>

    <x-ui.select wire:model="trainingCategoryId" label="Pasta" placeholder="Sem pasta">
        @foreach ($categories as $category)
            <option value="{{ $category->id }}">{{ $category->name }}</option>
        @endforeach
    </x-ui.select>

    <div class="grid grid-cols-2 gap-4">
        <x-ui.input wire:model="workloadHours" type="number" label="Carga horária (h)" />
        <x-ui.input wire:model="startsAt" type="date" label="Data de início" />
    </div>

    <x-ui.button type="submit">Salvar</x-ui.button>
</form>
