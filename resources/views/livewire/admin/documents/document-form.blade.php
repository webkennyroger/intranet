{{--
    Formulário de documento (admin), aberto em modal.
    Cadastra/edita título, descrição (texto livre), link (abre uma página,
    independente da descrição), pasta/categoria e arquivo — que pode ser um
    já existente na Biblioteca de Mídia (evita duplicar) ou um upload novo
    num único campo de arrastar-e-soltar (igual ao de Fotos) que aceita um
    ou vários arquivos de uma vez: soltar vários, só na criação, cria um
    Documento por arquivo (título = nome do arquivo). Documento novo entra
    sempre publicado — despublicar é uma ação separada na tabela (ícone de
    publicar/despublicar), não um campo deste formulário.
--}}
<form wire:submit="save" class="space-y-4">
    <x-ui.input wire:model="title" label="Título" />

    <div class="space-y-1.5">
        <label class="block text-sm font-medium" style="color: var(--text-secondary);">Descrição</label>
        <textarea wire:model="description" rows="3" class="w-full rounded-xl focus:outline-none focus:ring-2 focus:ring-(--focus-ring)/40 focus:border-(--focus-ring) transition-all duration-200 px-4 py-2.5 text-sm" style="background-color: var(--bg-input); border: 1px solid var(--border-color); color: var(--text-primary);"></textarea>
    </div>

    <div class="space-y-1.5">
        <label class="block text-sm font-medium" style="color: var(--text-secondary);">Link</label>
        <input type="url" wire:model="link" placeholder="https://..." class="w-full rounded-xl px-4 py-2.5 text-sm" style="background-color: var(--bg-input); border: 1px solid var(--border-color); color: var(--text-primary);">
        <p class="text-xs" style="color: var(--text-muted);">Pra abrir uma página externa (ex.: um sistema de terceiros). Pode existir junto com um arquivo enviado — cada um abre com seu próprio ícone.</p>
        @error('link') <p class="text-xs text-red-400">{{ $message }}</p> @enderror
    </div>

    @unless ($document)
        <p class="text-xs -mt-2" style="color: var(--text-muted);">Título, descrição e link não se aplicam ao soltar vários arquivos de uma vez — nesse caso cada arquivo vira um documento, nomeado igual ao arquivo.</p>
    @endunless

    <x-ui.select wire:model="documentCategoryId" label="Pasta" placeholder="Sem pasta">
        @foreach ($categories as $category)
            <option value="{{ $category->id }}">{{ $category->name }}</option>
        @endforeach
    </x-ui.select>

    <!-- Arquivo: novo (um ou vários, por arrastar-e-soltar) ou reaproveitado da Biblioteca de Mídia -->
    <div class="space-y-1.5">
        <label class="block text-sm font-medium" style="color: var(--text-secondary);">Arquivo</label>

        <div class="flex items-center gap-1 p-1 rounded-xl w-fit mb-2" style="background-color: var(--bg-elevated);">
            <button type="button" wire:click="$set('useExistingFile', false)" class="px-3 py-1.5 rounded-lg text-xs font-semibold whitespace-nowrap {{ ! $useExistingFile ? 'shadow-sm' : '' }}" style="{{ ! $useExistingFile ? 'background-color: var(--bg-card); color: var(--text-primary);' : 'color: var(--text-secondary);' }}">
                Enviar novo
            </button>
            <button type="button" wire:click="$set('useExistingFile', true)" class="px-3 py-1.5 rounded-lg text-xs font-semibold whitespace-nowrap {{ $useExistingFile ? 'shadow-sm' : '' }}" style="{{ $useExistingFile ? 'background-color: var(--bg-card); color: var(--text-primary);' : 'color: var(--text-secondary);' }}">
                Já existente na Biblioteca
            </button>
        </div>

        @if ($useExistingFile)
            @if ($existingFileLabel)
                <div class="flex items-center justify-between rounded-xl px-4 py-2.5 text-sm" style="background-color: var(--bg-input); border: 1px solid var(--border-color); color: var(--text-primary);">
                    <span class="truncate"><i class="fa-solid fa-file-circle-check mr-1.5" style="color: #059669;"></i>{{ $existingFileLabel }}</span>
                    <x-ui.tooltip text="Limpar seleção" class="shrink-0 ml-2">
                        <button type="button" wire:click="clearExistingFile" style="color: var(--text-muted);">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </x-ui.tooltip>
                </div>
            @else
                <input type="text" wire:model.live.debounce.300ms="existingFileSearch" placeholder="Buscar arquivo..." class="w-full rounded-xl px-4 py-2.5 text-sm mb-2" style="background-color: var(--bg-input); border: 1px solid var(--border-color); color: var(--text-primary);">
                <div class="max-h-48 overflow-y-auto rounded-xl" style="border: 1px solid var(--border-color);">
                    @forelse ($existingFileOptions as $option)
                        <button type="button" wire:click="pickExistingFile(@js($option['path']), @js($option['name']))" class="w-full flex items-center justify-between gap-2 px-4 py-2.5 text-left text-sm transition hover:bg-[var(--bg-hover)]" style="border-bottom: 1px solid var(--border-color); color: var(--text-primary);">
                            <span class="truncate"><i class="fa-solid fa-file mr-1.5 opacity-60"></i>{{ $option['name'] }}</span>
                            <x-ui.badge variant="info">{{ $option['source'] }}</x-ui.badge>
                        </button>
                    @empty
                        <p class="px-4 py-3 text-sm" style="color: var(--text-muted);">Nenhum arquivo encontrado.</p>
                    @endforelse
                </div>
            @endif
        @else
            @if ($document?->file_path)
                <p class="text-xs mb-1" style="color: var(--text-muted);">Arquivo atual: {{ basename($document->file_path) }}. Envie um novo para substituir.</p>
            @endif

            <x-ui.file-upload
                wire:model="files"
                :multiple="! $document"
                hint="Qualquer tipo de arquivo (máx. 10 MB cada). Pode selecionar ou arrastar vários de uma vez."
            />

            @if (count($files) > 0)
                <ul class="text-xs space-y-1 mt-2" style="color: var(--text-secondary);">
                    @foreach ($files as $index => $pendingFile)
                        <li class="flex items-center justify-between gap-2 rounded-lg px-3 py-1.5" style="background-color: var(--bg-elevated);">
                            <span class="truncate"><i class="fa-solid fa-file mr-1.5 opacity-60"></i>{{ $pendingFile->getClientOriginalName() }}</span>
                            <x-ui.tooltip text="Remover arquivo" class="shrink-0">
                                <button type="button" wire:click="removeFile({{ $index }})" style="color: var(--text-muted);">
                                    <i class="fa-solid fa-xmark"></i>
                                </button>
                            </x-ui.tooltip>
                        </li>
                    @endforeach
                </ul>
            @endif
        @endif
        @error('files') <p class="text-xs text-red-400">{{ $message }}</p> @enderror
        @error('files.*') <p class="text-xs text-red-400">{{ $message }}</p> @enderror
    </div>

    <x-ui.button type="submit" class="w-full justify-center">
        <i class="fa-solid fa-floppy-disk mr-1.5"></i> Salvar
    </x-ui.button>
</form>
