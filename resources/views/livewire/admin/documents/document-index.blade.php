{{--
    Tela de gestão de Documentos (admin).
    Lista os documentos disponibilizados aos servidores, com
    estatísticas e ações para criar, editar, publicar/despublicar
    e excluir.
--}}
<div class="space-y-4">
    <!-- Cabeçalho da Página -->
    <x-admin.page-header
        icon="file-lines"
        title="Documentos"
        subtitle="Gerencie os documentos disponibilizados para os servidores."
    >
        <x-slot:actions>
            <x-ui.button wire:click="closeForm" x-on:click="window.dispatchEvent(new CustomEvent('open-modal', { detail: 'document-form' }))">
                Novo documento
            </x-ui.button>
        </x-slot:actions>
    </x-admin.page-header>

    <!-- Estatísticas (Cards) -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <x-admin.stat-card label="Total de documentos" :value="$totalDocuments" sublabel="Cadastrados" />
        <x-admin.stat-card label="Publicados" :value="$publishedDocuments" sublabel="Visíveis para todos" valueColor="var(--admin-primary-dark)" />
        <x-admin.stat-card label="Rascunhos" :value="$draftDocuments" sublabel="Ainda não publicados" />
    </div>

    <!-- Tabela de Documentos -->
    @if ($documents->isEmpty())
        <x-ui.empty-state title="Nenhum documento cadastrado ainda." icon="document" />
    @else
        <x-ui.data-table selectable>
            <x-slot:header>
                <th class="px-4 py-3 w-8"><x-ui.table-checkbox header /></th>
                <th class="px-6 py-3"><i class="fa-solid fa-file-lines mr-1 opacity-60"></i> Título</th>
                <th class="px-6 py-3"><i class="fa-solid fa-circle-check mr-1 opacity-60"></i> Status</th>
                <th class="px-6 py-3"><i class="fa-solid fa-bolt mr-1 opacity-60"></i> Ações</th>
            </x-slot:header>

            @foreach ($documents as $document)
                <tr wire:key="document-{{ $document->id }}">
                    <td class="px-4 py-3"><x-ui.table-checkbox /></td>
                    <td class="px-6 py-3">
                        <a href="{{ \Illuminate\Support\Facades\Storage::url($document->file_path) }}" target="_blank" class="text-emerald-700 hover:underline">
                            {{ $document->title }}
                        </a>
                    </td>
                    <td class="px-6 py-3"><x-ui.badge :variant="$document->is_published ? 'success' : 'warning'">{{ $document->is_published ? 'Publicado' : 'Rascunho' }}</x-ui.badge></td>
                    <td class="px-6 py-3 space-x-3 whitespace-nowrap">
                        <x-ui.tooltip text="Editar">
                            <button wire:click="edit({{ $document->id }})" x-on:click="window.dispatchEvent(new CustomEvent('open-modal', { detail: 'document-form' }))" class="text-emerald-700">
                                <i class="fa-solid fa-pen"></i>
                            </button>
                        </x-ui.tooltip>
                        @can('content.publish')
                            <x-ui.tooltip :text="$document->is_published ? 'Despublicar' : 'Publicar'">
                                <button wire:click="togglePublish({{ $document->id }})" class="text-amber-700">
                                    <i class="fa-solid fa-{{ $document->is_published ? 'toggle-off' : 'toggle-on' }}"></i>
                                </button>
                            </x-ui.tooltip>
                        @endcan
                        @can('content.delete')
                            <x-ui.tooltip text="Excluir">
                                <button wire:click="delete({{ $document->id }})" wire:confirm="Confirma a exclusão deste documento?" class="text-red-700">
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
</div>
