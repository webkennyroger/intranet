<?php

namespace App\Livewire\Site\Council;

use App\Models\CouncilMember;
use App\Models\CouncilProcess;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Página institucional do conselho: lista os membros ativos (na ordem de
 * exibição definida no cadastro) e, abaixo, os processos SEI publicados,
 * com busca por número, assunto, relator/atribuído ou interessado — o
 * número do processo abre o link externo do SEI quando cadastrado.
 */
class CouncilIndex extends Component
{
    use WithPagination;

    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset('search');
    }

    /**
     * Isolada num island (ver council-index.blade.php): precisa estar
     * acessível via $this-> porque islands não têm acesso a variáveis de
     * view vindas de fora deles, só a propriedades/métodos do componente.
     */
    #[Computed]
    public function processes()
    {
        return CouncilProcess::where('is_published', true)
            ->when($this->search !== '', fn ($query) => $query->where('sei_number', 'like', "%{$this->search}%")
                ->orWhere('subject', 'like', "%{$this->search}%")
                ->orWhere('assigned_to', 'like', "%{$this->search}%")
                ->orWhere('interested_party', 'like', "%{$this->search}%"))
            ->orderByDesc('sei_number')
            ->paginate(20);
    }

    public function render()
    {
        return view('livewire.site.council.council-index', [
            'members' => CouncilMember::where('is_active', true)->orderBy('order')->get(),
        ]);
    }
}
