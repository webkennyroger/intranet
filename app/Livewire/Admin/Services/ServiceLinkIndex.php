<?php

namespace App\Livewire\Admin\Services;

use App\Models\ServiceLink;
use App\Services\ActivityLogger;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Tela admin "Gerenciar Serviços": lista os links de serviço, com
 * reordenação via drag-and-drop, ativação/desativação e exclusão.
 */
class ServiceLinkIndex extends Component
{
    public ?int $editingLinkId = null;

    #[On('service-link-saved')]
    public function refreshList(): void
    {
        $this->editingLinkId = null;
    }

    public function edit(int $linkId): void
    {
        $this->editingLinkId = $linkId ?: null;
    }

    public function closeForm(): void
    {
        $this->editingLinkId = null;
    }

    public function delete(int $linkId): void
    {
        $link = ServiceLink::findOrFail($linkId);
        $link->delete();
        ActivityLogger::deleted($link);
    }

    public function toggleActive(int $linkId): void
    {
        $link = ServiceLink::findOrFail($linkId);
        $willBeActive = ! $link->is_active;
        $link->update(['is_active' => $willBeActive]);
        $willBeActive ? ActivityLogger::activated($link) : ActivityLogger::deactivated($link);
    }

    /**
     * @param  array<int, int>  $ids
     */
    public function reorder(array $ids): void
    {
        $existingCount = ServiceLink::whereIn('id', $ids)->count();

        // Confere se $ids é exatamente o conjunto completo de links, sem
        // duplicados nem itens faltando, antes de reaplicar a ordem — o
        // front-end manda a lista inteira a cada drag-and-drop, e dados
        // adulterados ou fora de sincronia não devem bagunçar o campo "order".
        abort_if($existingCount !== count(array_unique($ids)) || $existingCount !== ServiceLink::count(), 403);

        foreach ($ids as $position => $id) {
            ServiceLink::whereKey($id)->update(['order' => $position]);
        }
    }

    public function render()
    {
        return view('livewire.admin.services.service-link-index', [
            'links' => ServiceLink::orderBy('order')->get(),
            'editingLink' => $this->editingLinkId ? ServiceLink::find($this->editingLinkId) : null,
            'totalLinks' => ServiceLink::count(),
            'activeLinks' => ServiceLink::where('is_active', true)->count(),
            'inactiveLinks' => ServiceLink::where('is_active', false)->count(),
        ])->layout('layouts.admin', ['title' => 'Gerenciar Serviços']);
    }
}
