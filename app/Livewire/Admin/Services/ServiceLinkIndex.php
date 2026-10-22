<?php

namespace App\Livewire\Admin\Services;

use App\Livewire\Concerns\ComputesWeeklyTrend;
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
    use ComputesWeeklyTrend;

    public ?int $editingLinkId = null;

    /** @var array<int, int> */
    public array $selected = [];

    #[On('service-link-saved')]
    public function refreshList(): void
    {
        $this->editingLinkId = null;
        $this->dispatch('close-modal', 'service-link-form');
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
        abort_unless(auth()->user()->can('services.delete'), 403);

        $link = ServiceLink::findOrFail($linkId);
        $link->delete();
        ActivityLogger::deleted($link);
        $this->dispatch('toast', title: 'Serviço', message: 'Serviço excluído com sucesso.', variant: 'danger');
    }

    public function toggleActive(int $linkId): void
    {
        abort_unless(auth()->user()->can('services.update'), 403);

        $link = ServiceLink::findOrFail($linkId);
        $willBeActive = ! $link->is_active;
        $link->update(['is_active' => $willBeActive]);
        $willBeActive ? ActivityLogger::activated($link) : ActivityLogger::deactivated($link);
        $this->dispatch('toast', title: 'Serviço', message: $willBeActive ? 'Serviço ativado com sucesso.' : 'Serviço desativado.', variant: $willBeActive ? 'success' : 'warning');
    }

    public function toggleSelectAllOnPage(): void
    {
        $pageIds = ServiceLink::orderBy('order')->pluck('id')->all();

        if (array_diff($pageIds, $this->selected) === []) {
            $this->selected = array_values(array_diff($this->selected, $pageIds));
        } else {
            $this->selected = array_values(array_unique(array_merge($this->selected, $pageIds)));
        }
    }

    public function deselectAll(): void
    {
        $this->selected = [];
    }

    public function bulkActivate(): void
    {
        abort_unless(auth()->user()->can('services.update'), 403);

        $links = ServiceLink::whereIn('id', $this->selected)->where('is_active', false)->get();

        foreach ($links as $link) {
            $link->update(['is_active' => true]);
            ActivityLogger::activated($link);
        }

        $this->selected = [];
        $message = $links->isNotEmpty() ? $links->count().' serviço(s) ativado(s) com sucesso.' : 'Nenhum serviço inativo selecionado para ativar.';
        $this->dispatch('toast', title: 'Serviços', message: $message, variant: $links->isNotEmpty() ? 'success' : 'warning');
    }

    public function bulkDeactivate(): void
    {
        abort_unless(auth()->user()->can('services.update'), 403);

        $links = ServiceLink::whereIn('id', $this->selected)->where('is_active', true)->get();

        foreach ($links as $link) {
            $link->update(['is_active' => false]);
            ActivityLogger::deactivated($link);
        }

        $this->selected = [];
        $message = $links->isNotEmpty() ? $links->count().' serviço(s) desativado(s).' : 'Nenhum serviço ativo selecionado.';
        $this->dispatch('toast', title: 'Serviços', message: $message, variant: 'warning');
    }

    public function bulkDelete(): void
    {
        abort_unless(auth()->user()->can('services.delete'), 403);

        $links = ServiceLink::whereIn('id', $this->selected)->get();

        foreach ($links as $link) {
            $link->delete();
            ActivityLogger::deleted($link);
        }

        $this->selected = [];
        $this->dispatch('toast', title: 'Serviços', message: $links->count().' serviço(s) excluído(s) com sucesso.', variant: 'danger');
    }

    /**
     * @param  array<int, int>  $ids
     */
    public function reorder(array $ids): void
    {
        abort_unless(auth()->user()->can('services.update'), 403);

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
        $links = ServiceLink::orderBy('order')->get();

        return view('livewire.admin.services.service-link-index', [
            'links' => $links,
            'allSelectedOnPage' => $links->isNotEmpty() && array_diff($links->pluck('id')->all(), $this->selected) === [],
            'editingLink' => $this->editingLinkId ? ServiceLink::find($this->editingLinkId) : null,
            'totalLinks' => ServiceLink::count(),
            'totalLinksTrend' => $this->weeklyTrend(ServiceLink::query(), 'created_at'),
            'activeLinks' => ServiceLink::where('is_active', true)->count(),
            'activeLinksTrend' => $this->weeklyTrend(ServiceLink::query()->where('is_active', true), 'created_at'),
            'inactiveLinks' => ServiceLink::where('is_active', false)->count(),
            'inactiveLinksTrend' => $this->weeklyTrend(ServiceLink::query()->where('is_active', false), 'created_at'),
        ])->layout('layouts.admin', ['title' => 'Gerenciar Serviços']);
    }
}
