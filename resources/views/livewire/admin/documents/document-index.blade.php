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
        <x-slot:actions>
            @if ($activeTab === 'documentos' && count($selected) > 0)
                <span class="text-xs font-semibold" style="color: var(--text-muted);">{{ count($selected) }} selecionado(s)</span>
                @can('documents.update')
                    <x-ui.button type="button" wire:click="bulkPublish" variant="secondary" size="sm">
                        <i class="fa-solid fa-check mr-1"></i> Publicar
                    </x-ui.button>
                    <x-ui.button type="button" wire:click="bulkUnpublish" variant="secondary" size="sm">
                        <i class="fa-solid fa-ban mr-1"></i> Despublicar
                    </x-ui.button>
                @endcan
                @can('documents.delete')
                    <x-ui.button type="button" @click="confirmAction('Confirma a exclusão de {{ count($selected) }} documento(s) selecionado(s)?', () => $wire.bulkDelete())" variant="danger" size="sm">
                        <i class="fa-solid fa-trash mr-1"></i> Excluir
                    </x-ui.button>
                @endcan
                <x-ui.button type="button" wire:click="deselectAll" variant="ghost" size="sm">Cancelar</x-ui.button>
            @elseif ($activeTab === 'documentos')
                <x-ui.button wire:click="closeForm" x-on:click="window.dispatchEvent(new CustomEvent('open-modal', { detail: 'document-form' }))">
                    Novo documento
                </x-ui.button>
            @endif
        </x-slot:actions>
    </x-admin.page-header>

    <!-- Abas -->
    <div class="flex items-center gap-1 p-1 rounded-xl w-fit" style="background-color: var(--bg-elevated);">
        <button type="button" wire:click="setTab('documentos')" class="flex items-center gap-2 pl-1.5 pr-4 py-1.5 rounded-lg text-sm font-semibold whitespace-nowrap {{ $activeTab === 'documentos' ? 'shadow-sm' : '' }}" style="{{ $activeTab === 'documentos' ? 'background-color: var(--bg-card); color: var(--text-primary);' : 'color: var(--text-secondary);' }}">
            <span class="w-7 h-7 inline-flex items-center justify-center rounded-lg" style="background-color: rgba(37, 99, 235, 0.1); color: #2563EB;">
                <i class="fa-solid fa-file-lines text-xs"></i>
            </span>
            Documentos
        </button>
        <button type="button" wire:click="setTab('pastas')" class="flex items-center gap-2 pl-1.5 pr-4 py-1.5 rounded-lg text-sm font-semibold whitespace-nowrap {{ $activeTab === 'pastas' ? 'shadow-sm' : '' }}" style="{{ $activeTab === 'pastas' ? 'background-color: var(--bg-card); color: var(--text-primary);' : 'color: var(--text-secondary);' }}">
            <span class="w-7 h-7 inline-flex items-center justify-center rounded-lg" style="background-color: rgba(217, 119, 6, 0.1); color: #D97706;">
                <i class="fa-solid fa-folder text-xs"></i>
            </span>
            Pastas
        </button>
    </div>

    @if ($activeTab === 'documentos')
        <!-- Estatísticas (Cards) -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <x-admin.stat-card label="Total de documentos" :value="$totalDocuments" sublabel="Cadastrados" />
            <x-admin.stat-card label="Publicados" :value="$publishedDocuments" sublabel="Visíveis para todos" valueColor="var(--admin-primary-dark)" />
            <x-admin.stat-card label="Rascunhos" :value="$draftDocuments" sublabel="Ainda não publicados" />
        </div>

        <!-- Busca -->
        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Buscar por título..."
            class="w-full rounded-lg px-4 py-2.5 text-sm placeholder:text-slate-400"
            style="background-color: var(--bg-card); border: 1px solid var(--border-color); color: var(--text-primary);" />

        <!-- Tabela de Documentos -->
        @if ($documents->isEmpty())
            <x-ui.empty-state title="Nenhum documento cadastrado ainda." icon="document" />
        @else
            <x-ui.data-table selectable>
                <x-slot:header>
                    <th class="px-4 py-3 w-8"><x-ui.table-checkbox header wire:click="toggleSelectAllOnPage" :checked="$allSelectedOnPage" /></th>
                    <th class="px-6 py-3"><i class="fa-solid fa-file-lines mr-1 opacity-60"></i> Título</th>
                    <th class="px-6 py-3"><i class="fa-solid fa-folder mr-1 opacity-60"></i> Pasta</th>
                    <th class="px-6 py-3"><i class="fa-solid fa-circle-check mr-1 opacity-60"></i> Status</th>
                    <th class="px-6 py-3"><i class="fa-solid fa-bolt mr-1 opacity-60"></i> Ações</th>
                </x-slot:header>

                @foreach ($documents as $document)
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
                    {{ $documents->links() }}
                </x-slot:footer>
            </x-ui.data-table>
        @endif

        <!-- Modal: Formulário de Documento -->
        <x-ui.modal name="document-form" maxWidth="lg">
            <x-slot:header>Documento</x-slot:header>
            <livewire:admin.documents.document-form :document="$editingDocument" :key="'document-form-'.($editingDocumentId ?? 'new')" />
        </x-ui.modal>
    @else
        <!-- Aba Pastas -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            <div class="lg:col-span-2">
                @if ($categories->isEmpty())
                    <x-ui.empty-state title="Nenhuma pasta cadastrada ainda." icon="folder" />
                @else
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        @foreach ($categories as $category)
                            <div class="rounded-2xl p-4" style="background-color: var(--bg-card); border: 1px solid var(--border-color);" wire:key="category-{{ $category->id }}">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="flex items-start gap-3 min-w-0">
                                        <span class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0" style="background-color: {{ $category->lightColor() }};">
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
                        <x-ui.input wire:model="categoryIcon" label="Ícone" placeholder="Ex: folder" hint="Nome de um ícone do FontAwesome (fa-solid), sem o prefixo fa-." />
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
