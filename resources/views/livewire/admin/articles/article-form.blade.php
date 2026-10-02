<form wire:submit="save" class="space-y-4" x-on:content-change="$wire.set('body', $event.detail)">
    <x-ui.input wire:model="title" label="Título" />
    <x-ui.input wire:model="excerpt" label="Resumo" />

    <x-ui.select wire:model="categoryId" label="Categoria">
        <option value="">Sem categoria</option>
        @foreach ($categories as $category)
            <option value="{{ $category->id }}">{{ $category->name }}</option>
        @endforeach
    </x-ui.select>

    <x-ui.color-picker wire:model="coverColor" label="Cor da capa" />
    <x-ui.input wire:model="coverIcon" label="Ícone da capa (Font Awesome)" hint="Ex: leaf, newspaper, bullhorn" />
    <x-ui.input wire:model="coverLabel" label="Rótulo da capa (opcional)" />

    <div>
        <label class="block text-sm font-medium mb-1.5" style="color: var(--text-secondary);">Conteúdo</label>
        <x-editor.tiptap name="body" :value="$body" placeholder="Escreva o conteúdo do artigo..." height="300px" />
        @error('body') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
    </div>

    <x-ui.button type="submit">Salvar</x-ui.button>
</form>
