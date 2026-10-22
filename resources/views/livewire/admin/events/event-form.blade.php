{{--
    Formulário de evento (admin), aberto em modal.
    Cadastra/edita título, descrição, local, link externo (transmissão ou
    inscrição), arquivo anexo (qualquer tipo, ex. programação em PDF),
    imagem de capa e datas de início/término.
--}}
<form wire:submit="save" class="space-y-4">
    <x-ui.input wire:model="title" label="Título" />

    @php $selectedCategory = $categories->firstWhere('id', $categoryId); @endphp
    <div class="space-y-1.5">
        <label class="block text-sm font-medium" style="color: var(--text-secondary);">Categoria</label>
        <div class="flex items-center gap-2">
            @if ($selectedCategory)
                <span class="w-4 h-4 rounded-full shrink-0" style="background-color: {{ $selectedCategory->color }};"></span>
            @endif
            <select wire:model.live="categoryId" class="w-full rounded-xl focus:outline-none focus:ring-2 focus:ring-(--focus-ring)/40 focus:border-(--focus-ring) transition-all duration-200 px-4 py-2.5 text-sm" style="background-color: var(--bg-input); border: 1px solid var(--border-color); color: var(--text-primary);">
                <option value="">Selecione uma categoria...</option>
                @foreach ($categories as $cat)
                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                @endforeach
            </select>
        </div>
        @error('categoryId') <p class="text-xs text-red-500">{{ $message }}</p> @enderror
    </div>

    <x-ui.textarea wire:model="description" label="Descrição" rows="3" />

    <div class="grid grid-cols-2 gap-4">
        <x-ui.input wire:model="location" label="Local" />
        <x-ui.input wire:model="link" type="url" label="Link" placeholder="https://..." hint="Transmissão, inscrição etc." />
    </div>

    <div class="grid grid-cols-2 gap-4">
        <x-ui.input wire:model="startsAt" type="datetime-local" label="Início" />
        <x-ui.input wire:model="endsAt" type="datetime-local" label="Término" />
    </div>

    <div class="grid grid-cols-2 gap-4">
        <!-- Arquivo anexo: já existente (evento, documento ou mídia) ou upload novo. As abas ficam
             sempre visíveis (mesmo com um arquivo já escolhido) pra sempre dar pra trocar a origem;
             o arquivo escolhido (enviado agora ou vindo da Biblioteca) sempre mostra um "x" pra remover. -->
        <div class="space-y-1.5">
            <label class="block text-sm font-medium" style="color: var(--text-secondary);">Arquivo anexo</label>

            <div class="flex items-center gap-1 p-1 rounded-xl w-fit mb-2" style="background-color: var(--bg-elevated);">
                <button type="button" wire:click="$set('useExistingFile', false)" class="px-3 py-1.5 rounded-lg text-xs font-semibold whitespace-nowrap {{ ! $useExistingFile ? 'shadow-sm' : '' }}" style="{{ ! $useExistingFile ? 'background-color: var(--bg-card); color: var(--text-primary);' : 'color: var(--text-secondary);' }}">
                    Enviar novo
                </button>
                <button type="button" wire:click="$set('useExistingFile', true)" class="px-3 py-1.5 rounded-lg text-xs font-semibold whitespace-nowrap {{ $useExistingFile ? 'shadow-sm' : '' }}" style="{{ $useExistingFile ? 'background-color: var(--bg-card); color: var(--text-primary);' : 'color: var(--text-secondary);' }}">
                    Já existente na Biblioteca
                </button>
            </div>

            @if ($file || $existingFilePath)
                <div class="flex items-center justify-between rounded-xl px-3 py-2 text-xs" style="background-color: var(--bg-input); border: 1px solid var(--border-color); color: var(--text-primary);">
                    <span class="truncate"><i class="fa-solid fa-file-circle-check mr-1.5" style="color: #059669;"></i>{{ $file ? $file->getClientOriginalName() : basename($existingFilePath) }}</span>
                    <button type="button" wire:click="removeFile" class="shrink-0 ml-2" style="color: var(--text-muted);">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
            @elseif ($useExistingFile)
                <input type="text" wire:model.live.debounce.300ms="existingFileSearch" placeholder="Buscar arquivo..." class="w-full rounded-xl px-4 py-2.5 text-sm mb-2" style="background-color: var(--bg-input); border: 1px solid var(--border-color); color: var(--text-primary);">
                <div class="max-h-48 overflow-y-auto rounded-xl" style="border: 1px solid var(--border-color);">
                    @forelse ($existingFileOptions as $option)
                        <button type="button" wire:click="pickExistingFile(@js($option['path']))" class="w-full flex items-center justify-between gap-2 px-4 py-2.5 text-left text-sm transition hover:bg-[var(--bg-hover)]" style="border-bottom: 1px solid var(--border-color); color: var(--text-primary);">
                            <span class="truncate"><i class="fa-solid fa-file mr-1.5 opacity-60"></i>{{ $option['name'] }}</span>
                            <x-ui.badge variant="info">{{ $option['source'] }}</x-ui.badge>
                        </button>
                    @empty
                        <p class="px-4 py-3 text-sm" style="color: var(--text-muted);">Nenhum arquivo encontrado.</p>
                    @endforelse
                </div>
            @else
                <x-ui.file-upload wire:model="file" compact />
                @error('file') <p class="text-xs text-red-500">{{ $message }}</p> @enderror
            @endif
        </div>

        <!-- Imagem de capa: já existente (evento ou mídia) ou upload novo. Mesmo padrão do arquivo
             anexo acima: abas sempre visíveis, miniatura escolhida sempre com "Remover imagem". -->
        <div class="space-y-1.5">
            <label class="block text-sm font-medium" style="color: var(--text-secondary);">Imagem de capa</label>

            <div class="flex items-center gap-1 p-1 rounded-xl w-fit mb-2" style="background-color: var(--bg-elevated);">
                <button type="button" wire:click="$set('useExistingCoverImage', false)" class="px-3 py-1.5 rounded-lg text-xs font-semibold whitespace-nowrap {{ ! $useExistingCoverImage ? 'shadow-sm' : '' }}" style="{{ ! $useExistingCoverImage ? 'background-color: var(--bg-card); color: var(--text-primary);' : 'color: var(--text-secondary);' }}">
                    Enviar novo
                </button>
                <button type="button" wire:click="$set('useExistingCoverImage', true)" class="px-3 py-1.5 rounded-lg text-xs font-semibold whitespace-nowrap {{ $useExistingCoverImage ? 'shadow-sm' : '' }}" style="{{ $useExistingCoverImage ? 'background-color: var(--bg-card); color: var(--text-primary);' : 'color: var(--text-secondary);' }}">
                    Já existente na Biblioteca
                </button>
            </div>

            @if ($coverImage)
                <img src="{{ $coverImage->temporaryUrl() }}" class="w-full h-40 object-cover rounded-xl" alt="Pré-visualização" />
                <button type="button" wire:click="removeCoverImage" class="text-xs font-semibold text-red-600 hover:underline">Remover imagem</button>
            @elseif ($existingCoverImage)
                <img src="{{ \Illuminate\Support\Facades\Storage::url($existingCoverImage) }}" class="w-full h-40 object-cover rounded-xl" alt="Imagem atual" />
                <button type="button" wire:click="removeCoverImage" class="text-xs font-semibold text-red-600 hover:underline">Remover imagem</button>
            @elseif ($useExistingCoverImage)
                <input type="text" wire:model.live.debounce.300ms="existingCoverImageSearch" placeholder="Buscar imagem..." class="w-full rounded-xl px-4 py-2.5 text-sm mb-2" style="background-color: var(--bg-input); border: 1px solid var(--border-color); color: var(--text-primary);">
                <div class="grid grid-cols-3 gap-2 max-h-56 overflow-y-auto">
                    @forelse ($existingCoverImageOptions as $option)
                        <button type="button" wire:click="pickExistingCoverImage(@js($option['path']))" class="relative rounded-lg overflow-hidden" style="border: 1px solid var(--border-color);">
                            <img src="{{ \Illuminate\Support\Facades\Storage::url($option['path']) }}" class="w-full h-16 object-cover" alt="{{ $option['name'] }}" />
                            <span class="absolute bottom-0 inset-x-0 text-[10px] px-1 py-0.5 truncate bg-black/60 text-white">{{ $option['name'] }}</span>
                        </button>
                    @empty
                        <p class="col-span-3 text-xs py-3 text-center" style="color: var(--text-muted);">Nenhuma imagem encontrada.</p>
                    @endforelse
                </div>
            @else
                <x-ui.file-upload wire:model="coverImage" accept="image/*" compact />
                @error('coverImage') <p class="text-xs text-red-500">{{ $message }}</p> @enderror
            @endif
        </div>
    </div>

    <x-ui.button type="submit">Salvar</x-ui.button>
</form>
