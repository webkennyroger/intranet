{{--
    Tela de gestão de Documentos (admin), em duas abas: "Documentos" lista
    os documentos cadastrados (criar, editar, publicar/despublicar,
    excluir); "Pastas" gerencia as categorias usadas para organizá-los
    (criar, editar, excluir — cada pasta tem sua própria cor).
--}}
<div class="space-y-4">
    <!-- Cabeçalho da Página -->
    <x-admin.page-header
        icon="file-lines"
        title="Documentos"
        subtitle="Gerencie os documentos disponibilizados para os servidores."
        :breadcrumbs="[['label' => 'Painel', 'href' => route('dashboard')], ['label' => 'Documentos']]"
    >
        @if ($activeTab === 'documentos')
            <x-slot:actions>
                <x-ui.button wire:click="closeForm" x-on:click="window.dispatchEvent(new CustomEvent('open-modal', { detail: 'document-form' }))">
                    Novo documento
                </x-ui.button>
            </x-slot:actions>
        @endif
    </x-admin.page-header>

    @if ($activeTab === 'documentos')
        <!-- Toolbar: total (compacto) + busca na mesma linha, em vez de um
             grid de cards de dashboard inteiro acima de um card de busca
             enorme — mesmo padrão de Capacitações. -->
        <div class="flex flex-col sm:flex-row items-stretch gap-3">
            <div class="ui-card px-4 py-2.5 flex items-center gap-3 shrink-0" style="border-top: 2px solid #2563EB;">
                <span class="w-9 h-9 rounded-lg flex items-center justify-center shrink-0" style="background-color: color-mix(in srgb, #2563EB 14%, transparent);">
                    <i class="fa-solid fa-file-lines text-sm" style="color: #2563EB;"></i>
                </span>
                <div class="whitespace-nowrap">
                    <p class="text-[10px] font-bold uppercase tracking-wide leading-none" style="color: var(--text-muted);">Total</p>
                    <p class="text-lg font-black leading-tight" style="color: var(--text-primary);">{{ $totalDocuments }}</p>
                </div>
            </div>

            <div class="ui-card px-4 py-2.5 flex items-center gap-2.5 flex-1">
                <i class="fa-solid fa-magnifying-glass text-xs shrink-0" style="color: var(--text-muted);"></i>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Buscar por título..."
                    class="flex-1 min-w-0 bg-transparent text-sm focus:outline-none"
                    style="color: var(--text-primary);" />
            </div>
        </div>
    @endif

    <!-- Abas: centralizadas, com cantos maiores e sombra na ativa — mesmo
         padrão de Capacitações. Borda própria (não só a cor de fundo) pra
         ficar visível tanto no claro quanto no escuro. Hover igual ao das
         pastas de Documentos do site: risco colorido no topo que aparece
         ao passar o mouse + leve elevação com sombra. -->
    <div class="flex items-center justify-center gap-2 p-1.5 rounded-2xl w-fit mx-auto" style="background-color: var(--bg-elevated); border: 1px solid var(--border-color);">
        <button type="button" wire:click="setTab('documentos')" class="group relative overflow-hidden flex items-center gap-2.5 pl-2 pr-5 py-2 rounded-xl text-sm font-bold whitespace-nowrap transition {{ $activeTab === 'documentos' ? 'shadow-md' : 'hover:-translate-y-0.5 hover:shadow-md' }}" style="{{ $activeTab === 'documentos' ? 'background-color: var(--bg-card); color: var(--text-primary);' : 'color: var(--text-secondary);' }}">
            <span class="absolute left-0 right-0 top-0 h-1 transition-transform {{ $activeTab === 'documentos' ? '' : 'scale-x-0 group-hover:scale-x-100' }}" style="background-color: #2563EB;"></span>
            <span class="w-8 h-8 inline-flex items-center justify-center rounded-lg transition group-hover:scale-105" style="background-color: color-mix(in srgb, #2563EB 14%, transparent); color: #2563EB;">
                <i class="fa-solid fa-file-lines text-sm"></i>
            </span>
            Documentos
        </button>
        <button type="button" wire:click="setTab('pastas')" class="group relative overflow-hidden flex items-center gap-2.5 pl-2 pr-5 py-2 rounded-xl text-sm font-bold whitespace-nowrap transition {{ $activeTab === 'pastas' ? 'shadow-md' : 'hover:-translate-y-0.5 hover:shadow-md' }}" style="{{ $activeTab === 'pastas' ? 'background-color: var(--bg-card); color: var(--text-primary);' : 'color: var(--text-secondary);' }}">
            <span class="absolute left-0 right-0 top-0 h-1 transition-transform {{ $activeTab === 'pastas' ? '' : 'scale-x-0 group-hover:scale-x-100' }}" style="background-color: #D97706;"></span>
            <span class="w-8 h-8 inline-flex items-center justify-center rounded-lg transition group-hover:scale-105" style="background-color: color-mix(in srgb, #D97706 14%, transparent); color: #D97706;">
                <i class="fa-solid fa-folder text-sm"></i>
            </span>
            Pastas
        </button>
    </div>

    {{-- Tabela de Documentos — isolada num island: selecionar linhas,
         paginar e publicar/excluir só atualizam esta região. O island
         fica fora do @if da aba (islands não podem ficar dentro de
         @if/@foreach) e checa a aba por dentro, com $this->activeTab. --}}
    @island(name: 'documents-list', always: true)
        @if ($this->activeTab === 'documentos')
            <div class="space-y-4">
                @if (count($this->selected) > 0)
                    <div class="flex items-center justify-end gap-2">
                        <span class="text-xs font-semibold" style="color: var(--text-muted);">{{ count($this->selected) }} selecionado(s)</span>
                        @can('documents.update')
                            <x-ui.button type="button" wire:click="bulkPublish" variant="secondary" size="sm">
                                <i class="fa-solid fa-check mr-1"></i> Publicar
                            </x-ui.button>
                            <x-ui.button type="button" wire:click="bulkUnpublish" variant="secondary" size="sm">
                                <i class="fa-solid fa-ban mr-1"></i> Despublicar
                            </x-ui.button>
                        @endcan
                        @can('documents.delete')
                            <x-ui.button type="button" @click="confirmAction('Confirma a exclusão de {{ count($this->selected) }} documento(s) selecionado(s)?', () => $wire.bulkDelete())" variant="danger" size="sm">
                                <i class="fa-solid fa-trash mr-1"></i> Excluir
                            </x-ui.button>
                        @endcan
                        <x-ui.button type="button" wire:click="deselectAll" variant="ghost" size="sm">Cancelar</x-ui.button>
                    </div>
                @endif

                @if ($this->documents->isEmpty())
                    <x-ui.empty-state title="Nenhum documento cadastrado ainda." icon="document" />
                @else
                    <x-ui.data-table selectable>
                        <x-slot:header>
                            <th class="px-4 py-3 w-8"><x-ui.table-checkbox header wire:click="toggleSelectAllOnPage" :checked="$this->allSelectedOnPage" /></th>
                            <th class="px-6 py-3"><i class="fa-solid fa-file-lines mr-1 opacity-60"></i> Título</th>
                            <th class="px-6 py-3"><i class="fa-solid fa-folder mr-1 opacity-60"></i> Pasta</th>
                            <th class="px-6 py-3"><i class="fa-solid fa-circle-check mr-1 opacity-60"></i> Status</th>
                            <th class="px-6 py-3"><i class="fa-solid fa-bolt mr-1 opacity-60"></i> Ações</th>
                        </x-slot:header>

                        @foreach ($this->documents as $document)
                            <tr wire:key="document-{{ $document->id }}">
                                <td class="px-4 py-3"><x-ui.table-checkbox wire:model.live="selected" value="{{ $document->id }}" /></td>
                                <td class="px-6 py-3">
                                    <a href="{{ $document->url() }}" target="_blank" class="inline-flex items-center gap-2 text-emerald-700 hover:underline">
                                        <span class="w-7 h-7 inline-flex items-center justify-center rounded-lg shrink-0 {{ $document->iconClasses()['bg'] }}">
                                            <i class="fa-solid fa-{{ $document->icon() }} text-xs {{ $document->iconClasses()['text'] }}"></i>
                                        </span>
                                        {{ $document->title }}
                                    </a>
                                </td>
                                <td class="px-6 py-3">
                                    @if ($document->category)
                                        <span class="inline-flex items-center gap-1 rounded-full text-xs font-semibold px-2.5 py-1" style="background-color: {{ $document->category->lightColor() }}; color: {{ $document->category->color }};">
                                            <i class="fa-solid fa-{{ $document->category->icon }}"></i>{{ $document->category->name }}
                                        </span>
                                    @else
                                        <span style="color: var(--text-muted);">—</span>
                                    @endif
                                </td>
                                <td class="px-6 py-3"><x-ui.badge :variant="$document->is_published ? 'success' : 'warning'">{{ $document->is_published ? 'Publicado' : 'Rascunho' }}</x-ui.badge></td>
                                <td class="px-6 py-3 space-x-2 whitespace-nowrap">
                                    <x-ui.tooltip text="Editar">
                                        <button wire:click="edit({{ $document->id }})" x-on:click="window.dispatchEvent(new CustomEvent('open-modal', { detail: 'document-form' }))" class="w-9 h-9 inline-flex items-center justify-center rounded transition hover:brightness-95" style="background-color: rgba(37, 99, 235, 0.1); color: #2563EB;">
                                            <i class="fa-solid fa-pen"></i>
                                        </button>
                                    </x-ui.tooltip>
                                    @can('documents.update')
                                        <x-ui.tooltip :text="$document->is_published ? 'Despublicar' : 'Publicar'">
                                            <button wire:click="togglePublish({{ $document->id }})" class="w-9 h-9 inline-flex items-center justify-center rounded transition hover:brightness-95" style="background-color: rgba(217, 119, 6, 0.1); color: #D97706;">
                                                <i class="fa-solid fa-{{ $document->is_published ? 'toggle-off' : 'toggle-on' }}"></i>
                                            </button>
                                        </x-ui.tooltip>
                                    @endcan
                                    @can('documents.delete')
                                        <x-ui.tooltip text="Excluir">
                                            <button @click="confirmAction('Confirma a exclusão deste documento?', () => $wire.delete({{ $document->id }}))" class="w-9 h-9 inline-flex items-center justify-center rounded transition hover:brightness-95" style="background-color: rgba(220, 38, 38, 0.1); color: #DC2626;">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </x-ui.tooltip>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach

                        <x-slot:footer>
                            {{ $this->documents->links() }}
                        </x-slot:footer>
                    </x-ui.data-table>
                @endif
            </div>
        @endif
    @endisland

    <!-- Modal: Formulário de Documento -->
    <x-ui.modal name="document-form" maxWidth="lg">
        <x-slot:header>
            <div class="flex items-center gap-3">
                <span class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0" style="background: linear-gradient(135deg, var(--admin-primary, var(--brand-primary, #0C4A34)), color-mix(in srgb, var(--admin-primary, var(--brand-primary, #0C4A34)) 70%, black));">
                    <i class="fa-solid fa-file-lines text-white"></i>
                </span>
                <div>
                    <span class="text-[10px] font-bold uppercase tracking-wide" style="color: var(--admin-primary, var(--brand-primary, #0C4A34));">Documentos</span>
                    <p class="font-black text-base leading-snug" style="color: var(--text-primary);">{{ $editingDocument ? 'Editar documento' : 'Novo documento' }}</p>
                </div>
            </div>
        </x-slot:header>
        <livewire:admin.documents.document-form :document="$editingDocument" :key="'document-form-'.($editingDocumentId ?? 'new')" />
    </x-ui.modal>

    @if ($activeTab !== 'documentos')
        <!-- Aba Pastas -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            <div class="lg:col-span-2">
                @if ($categories->isEmpty())
                    <x-ui.empty-state title="Nenhuma pasta cadastrada ainda." icon="folder" />
                @else
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        @foreach ($categories as $category)
                            <div class="group relative overflow-hidden rounded-2xl p-4 transition hover:-translate-y-0.5 hover:shadow-md" style="background-color: var(--bg-card); border: 1px solid var(--border-color);" wire:key="category-{{ $category->id }}">
                                <span class="absolute left-0 right-0 top-0 h-1 scale-x-0 transition-transform group-hover:scale-x-100" style="background-color: {{ $category->color }};"></span>

                                <div class="flex items-start justify-between gap-3">
                                    <div class="flex items-start gap-3 min-w-0">
                                        <span class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 transition group-hover:scale-105" style="background-color: {{ $category->lightColor() }};">
                                            <i class="fa-solid fa-{{ $category->icon }}" style="color: {{ $category->color }};"></i>
                                        </span>
                                        <div class="min-w-0">
                                            <div class="font-semibold truncate" style="color: var(--text-primary);">{{ $category->name }}</div>
                                            <div class="text-xs" style="color: var(--text-muted);">{{ $category->documents_count }} {{ $category->documents_count === 1 ? 'documento' : 'documentos' }}</div>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2 shrink-0">
                                        <x-ui.tooltip text="Editar">
                                            <button wire:click="editCategory({{ $category->id }})" class="w-9 h-9 inline-flex items-center justify-center rounded transition hover:brightness-95" style="background-color: rgba(37, 99, 235, 0.1); color: #2563EB;">
                                                <i class="fa-solid fa-pen"></i>
                                            </button>
                                        </x-ui.tooltip>
                                        <x-ui.tooltip text="Excluir">
                                            <button @click="confirmAction(@js('Confirma a exclusão da pasta "'.$category->name.'"? Os documentos dela ficarão sem pasta.'), () => $wire.deleteCategory({{ $category->id }}))" class="w-9 h-9 inline-flex items-center justify-center rounded transition hover:brightness-95" style="background-color: rgba(220, 38, 38, 0.1); color: #DC2626;">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </x-ui.tooltip>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Painel: Criar/Editar Pasta -->
            <div>
                <x-ui.card>
                    <x-slot:header>
                        <div>
                            <div class="font-semibold" style="color: var(--text-primary);">{{ $editingCategory ? 'Editar pasta' : 'Nova pasta' }}</div>
                            <p class="text-xs mt-0.5" style="color: var(--text-muted);">{{ $editingCategory ? 'Altere os dados da pasta selecionada.' : 'Crie uma nova pasta pra organizar documentos.' }}</p>
                        </div>
                    </x-slot:header>

                    <form wire:submit="saveCategory" class="space-y-4">
                        <x-ui.input wire:model="categoryName" label="Nome *" placeholder="Ex: Portarias" />
                        <x-ui.autocomplete wire:model="categoryIcon" :options="\App\Services\IconCatalog::suggestions()" label="Ícone" placeholder="Ex: folder" hint="Nome de um ícone do FontAwesome (fa-solid), sem o prefixo fa-." />
                        <x-ui.color-picker wire:model="categoryColor" label="Cor" hint="Usada no selo e no ícone desta pasta." />

                        <div class="flex items-center gap-3 pt-2">
                            <x-ui.button type="submit" class="flex-1">
                                <i class="fa-solid fa-{{ $editingCategory ? 'check' : 'plus' }} mr-1"></i>
                                {{ $editingCategory ? 'Salvar alterações' : 'Criar pasta' }}
                            </x-ui.button>
                            @if ($editingCategory)
                                <x-ui.button type="button" wire:click="cancelCategoryEdit" variant="ghost">Cancelar</x-ui.button>
                            @endif
                        </div>
                    </form>
                </x-ui.card>
            </div>
        </div>
    @endif
</div>
