{{--
    Tela admin "Categorias" do Mural: grade de cards com a contagem real de
    artigos por categoria e um painel lateral fixo pra criar/editar — sem
    modal nem página separada, o formulário já fica sempre visível ao lado.
--}}
<div class="space-y-4">
    <!-- Cabeçalho da Página -->
    <x-admin.page-header
        icon="folder"
        title="Categorias"
        subtitle="Organize os artigos do Mural por categoria."
        :breadcrumbs="[['label' => 'Painel', 'href' => route('dashboard')], ['label' => 'Categorias']]"
    />

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <!-- Grade de Categorias -->
        <div class="lg:col-span-2">
            @if ($categories->isEmpty())
                <x-ui.empty-state title="Nenhuma categoria cadastrada ainda." icon="folder" />
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    @foreach ($categories as $category)
                        <div class="rounded-2xl p-4" style="background-color: var(--bg-card); border: 1px solid var(--border-color);" wire:key="category-{{ $category->id }}">
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex items-start gap-3 min-w-0">
                                    <span class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0" style="background-color: {{ $category->lightColor() }};">
                                        <i class="fa-solid fa-folder" style="color: {{ $category->color }};"></i>
                                    </span>
                                    <div class="min-w-0">
                                        <div class="font-semibold truncate" style="color: var(--text-primary);">{{ $category->name }}</div>
                                        <div class="text-xs" style="color: var(--text-muted);">{{ $category->articles_count }} {{ $category->articles_count === 1 ? 'post' : 'posts' }} · Público</div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2 shrink-0">
                                    <x-ui.tooltip text="Editar">
                                        <button wire:click="edit({{ $category->id }})" class="w-9 h-9 inline-flex items-center justify-center rounded transition hover:brightness-95" style="background-color: rgba(37, 99, 235, 0.1); color: #2563EB;">
                                            <i class="fa-solid fa-pen"></i>
                                        </button>
                                    </x-ui.tooltip>
                                    <x-ui.tooltip text="Excluir">
                                        <button @click="confirmAction(@js('Confirma a exclusão da categoria "'.$category->name.'"? Ela será removida de todos os artigos que a usam.'), () => $wire.delete({{ $category->id }}))" class="w-9 h-9 inline-flex items-center justify-center rounded transition hover:brightness-95" style="background-color: rgba(220, 38, 38, 0.1); color: #DC2626;">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </x-ui.tooltip>
                                </div>
                            </div>

                            @if ($category->description)
                                <p class="text-xs mt-2 line-clamp-2" style="color: var(--text-secondary);">{{ $category->description }}</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Painel: Criar/Editar Categoria -->
        <div>
            <x-ui.card>
                <x-slot:header>
                    <div>
                        <div class="font-semibold" style="color: var(--text-primary);">{{ $editing ? 'Editar categoria' : 'Nova categoria' }}</div>
                        <p class="text-xs mt-0.5" style="color: var(--text-muted);">{{ $editing ? 'Altere os dados da categoria selecionada.' : 'Crie uma nova categoria de conteúdo.' }}</p>
                    </div>
                </x-slot:header>

                <form wire:submit="save" class="space-y-4">
                    <x-ui.input wire:model.live="name" label="Nome *" placeholder="Ex: Tutoriais" />
                    <x-ui.input wire:model="description" label="Descrição" placeholder="Resumo curto exibido no card da categoria." />
                    <x-ui.color-picker wire:model="color" label="Cor" hint="Usada nos selos desta categoria no Mural, na Início etc." />

                    <div class="flex items-center gap-3 pt-2">
                        <x-ui.button type="submit" class="flex-1">
                            <i class="fa-solid fa-{{ $editing ? 'check' : 'plus' }} mr-1"></i>
                            {{ $editing ? 'Salvar alterações' : 'Criar categoria' }}
                        </x-ui.button>
                        @if ($editing)
                            <x-ui.button type="button" wire:click="cancelEdit" variant="ghost">Cancelar</x-ui.button>
                        @endif
                    </div>
                </form>
            </x-ui.card>
        </div>
    </div>
</div>
