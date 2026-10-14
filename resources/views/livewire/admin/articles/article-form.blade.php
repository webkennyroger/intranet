{{--
    Página admin de criação/edição de artigo (não é mais modal), em duas
    colunas: conteúdo (título, descrição, resumo, campos personalizados) à
    esquerda e metadados (publicação/agendamento, categorias — um artigo
    pode ter várias —, comentários, autor, imagem em destaque) à direita.
    "Salvar" grava e volta pra listagem; "Aplicar" grava sem saber da
    página, só pra conferir se o resultado ficou correto.
--}}
<div class="space-y-4">
    <!-- Cabeçalho da Página -->
    <x-admin.page-header
        icon="newspaper"
        :title="$article ? 'Editar Artigo' : 'Novo Artigo'"
        subtitle="Preencha o conteúdo do artigo que será publicado no Mural."
        :breadcrumbs="[['label' => 'Painel', 'href' => route('dashboard')], ['label' => 'Notícias', 'href' => route('admin.articles.index')], ['label' => $article ? 'Editar Artigo' : 'Novo Artigo']]"
    >
        <x-slot:actions>
            @if ($article)
                @can('articles.update')
                    <x-ui.button type="button" wire:click="togglePublish" :variant="$article->is_published ? 'warning' : 'primary'">
                        <i class="fa-solid fa-{{ $article->is_published ? 'ban' : 'check' }} mr-1"></i>
                        {{ $article->is_published ? 'Despublicar' : 'Publicar' }}
                    </x-ui.button>
                @endcan
            @endif

            <x-ui.button :href="route('admin.articles.index')" variant="danger">Cancelar</x-ui.button>
            <x-ui.button type="button" wire:click="apply" variant="info">Aplicar</x-ui.button>
            <x-ui.button type="button" wire:click="save">Salvar</x-ui.button>
        </x-slot:actions>
    </x-admin.page-header>

    <form wire:submit="save" x-on:content-change="$wire.set('body', $event.detail)">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            <!-- Coluna principal: conteúdo -->
            <div class="lg:col-span-2 space-y-4">
                <x-ui.card>
                    <div class="space-y-4">
                        <x-ui.input wire:model="title" label="Título" />
                    </div>
                </x-ui.card>

                <!-- Resumo -->
                <x-ui.card>
                    <x-ui.input wire:model="excerpt" label="Resumo" hint="Usado como prévia do artigo nas listagens." />
                </x-ui.card>

                <x-ui.card>
                    <div class="space-y-4">
                        <label class="block text-sm font-medium mb-1.5" style="color: var(--text-secondary);">Descrição</label>
                        <x-editor.tiptap name="body" :value="$body" placeholder="Escreva o conteúdo do artigo..." height="300px" />
                        @error('body') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                </x-ui.card>


            </div>

            <!-- Coluna lateral: metadados -->
            <div class="space-y-4">
                <!-- Publicado -->
                <x-ui.card>
                   
                    <div class="space-y-3 text-sm">
                        <label class="block text-sm font-medium mb-2" style="color: var(--text-secondary);">Quando publicar</label>
                        <x-ui.toggle-group wire:model.live="publishMode" :options="['now' => 'Postar agora', 'schedule' => 'Agendar']" />
                

                        @if ($publishMode === 'schedule')
                            <div class="grid grid-cols-2 gap-3">
                                <x-ui.input type="date" wire:model="scheduledDate" label="Data" />
                                <x-ui.time-picker wire:model="scheduledTime" label="Hora" />
                            </div>
                        @endif
                        @error('scheduledDate') <p class="text-xs text-red-500">{{ $message }}</p> @enderror
                        @error('scheduledTime') <p class="text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>

                    @unless ($article)
                        <x-slot:footer>
                            <p class="text-xs" style="color: var(--text-muted);">{{ $publishMode === 'schedule' ? 'Vai ser publicado automaticamente na data/hora escolhida.' : 'Vai ser publicado assim que salvar.' }}</p>
                        </x-slot:footer>
                    @endunless
                </x-ui.card>

                <!-- Categorias -->
                <x-ui.card>
                    <x-slot:header><span class="font-semibold" style="color: var(--text-primary);">Categorias</span></x-slot:header>
                    <div class="space-y-3">
                        @if ($selectedCategories->isNotEmpty())
                            <div class="flex flex-wrap gap-2">
                                @foreach ($selectedCategories as $category)
                                    <span class="inline-flex items-center gap-1.5 rounded-full text-xs font-semibold px-2.5 py-1" style="background-color: {{ $category->lightColor() }}; color: {{ $category->color }};">
                                        {{ $category->name }}
                                        <button type="button" wire:click="removeCategory({{ $category->id }})" class="hover:opacity-70">
                                            <i class="fa-solid fa-xmark"></i>
                                        </button>
                                    </span>
                                @endforeach
                            </div>
                        @endif

                        @if ($availableCategories->isNotEmpty())
                            <div class="flex gap-2">
                                <div class="flex-1">
                                    <x-ui.select wire:model="newCategoryId">
                                        @foreach ($availableCategories as $category)
                                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                                        @endforeach
                                    </x-ui.select>
                                </div>
                                <x-ui.button type="button" wire:click="addCategory" size="sm">Adicionar</x-ui.button>
                            </div>
                        @endif
                    </div>
                </x-ui.card>

                <!-- Imagem em destaque -->
                <x-ui.card>
                    <x-slot:header><span class="font-semibold" style="color: var(--text-primary);">Imagem em destaque</span></x-slot:header>
                    <div class="space-y-3">
                        @if ($featuredImage)
                            <img src="{{ $featuredImage->temporaryUrl() }}" class="w-full h-32 object-cover rounded-xl" alt="Pré-visualização" />
                        @elseif ($existingFeaturedImage)
                            <img src="{{ \Illuminate\Support\Facades\Storage::url($existingFeaturedImage) }}" class="w-full h-32 object-cover rounded-xl" alt="Imagem atual" />
                        @else
                            <div class="w-full h-32 rounded-xl flex items-center justify-center text-xs font-semibold uppercase tracking-wide" style="background-color: var(--bg-elevated); color: var(--text-muted);">
                                Sem imagem
                            </div>
                        @endif

                        <x-ui.file-upload wire:model="featuredImage" accept="image/*" />
                        @error('featuredImage') <p class="text-xs text-red-500">{{ $message }}</p> @enderror

                        @if ($existingFeaturedImage || $featuredImage)
                            <button type="button" wire:click="removeFeaturedImage" class="text-xs font-semibold text-red-600 hover:underline">Remover imagem</button>
                        @endif

                        <div class="pt-2 space-y-3" style="border-top: 1px solid var(--border-color);">
                            <p class="text-xs" style="color: var(--text-muted);">Usada quando não há imagem: cor e ícone de reserva.</p>
                            <x-ui.color-picker wire:model="coverColor" label="Cor da capa" />
                            <x-ui.input wire:model="coverIcon" label="Ícone da capa (Font Awesome)" hint="Ex: leaf, newspaper, bullhorn" />
                            <x-ui.input wire:model="coverLabel" label="Rótulo da capa (opcional)" />
                        </div>
                    </div>
                </x-ui.card>

                <!-- Campos Personalizados -->
                <x-ui.card>
                    <x-slot:header><span class="font-semibold" style="color: var(--text-primary);">Campos personalizados</span></x-slot:header>
                    <div class="space-y-4">
                        @foreach ($customFields as $index => $field)
                            <div class="rounded-xl p-4 space-y-3" style="background-color: var(--bg-elevated);" wire:key="custom-field-{{ $index }}">
                                <div class="flex items-center justify-between gap-3">
                                    <div class="flex-1">
                                        <x-ui.select wire:model.live="customFields.{{ $index }}.type" label="Tipo" :placeholder="false">
                                            <option value="texto">Texto</option>
                                            <option value="link">Botão com link</option>
                                            <option value="arquivo">Arquivo para baixar</option>
                                            <option value="imagem">Imagem</option>
                                        </x-ui.select>
                                    </div>
                                    <button type="button" wire:click="removeCustomField({{ $index }})" class="text-red-600 hover:text-red-700 mt-6">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </div>

                                <x-ui.input wire:model="customFields.{{ $index }}.label" label="Rótulo" placeholder="Ex: Edital, Manual, Vídeo institucional..." />

                                @if (in_array($field['type'], ['texto', 'link']))
                                    <x-ui.input
                                        wire:model="customFields.{{ $index }}.value"
                                        :label="$field['type'] === 'link' ? 'URL do link' : 'Valor'"
                                        :placeholder="$field['type'] === 'link' ? 'https://...' : null"
                                    />
                                @else
                                    @if ($field['existingPath'])
                                        <div class="text-xs" style="color: var(--text-muted);">
                                            @if ($field['type'] === 'imagem')
                                                <img src="{{ \Illuminate\Support\Facades\Storage::url($field['existingPath']) }}" class="h-20 rounded-lg mb-1" alt="{{ $field['label'] }}" />
                                            @else
                                                <i class="fa-solid fa-file mr-1"></i> {{ basename($field['existingPath']) }}
                                            @endif
                                            — envie outro arquivo para substituir.
                                        </div>
                                    @endif
                                    <x-ui.file-upload wire:model="customFields.{{ $index }}.file" :accept="$field['type'] === 'imagem' ? 'image/*' : '*'" />
                                    @error("customFields.{$index}.file") <p class="text-xs text-red-500">{{ $message }}</p> @enderror
                                @endif
                            </div>
                        @endforeach

                        <x-ui.button type="button" wire:click="addCustomField" variant="secondary">
                            <i class="fa-solid fa-plus mr-1"></i> Adicionar campo personalizado
                        </x-ui.button>
                    </div>
                </x-ui.card>
            </div>
        </div>
    </form>
</div>
