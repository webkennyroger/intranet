<?php

namespace App\Livewire\Admin\Services;

use App\Models\ServiceLink;
use Livewire\Attributes\On;
use Livewire\Component;

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
        ServiceLink::findOrFail($linkId)->delete();
    }

    public function toggleActive(int $linkId): void
    {
        $link = ServiceLink::findOrFail($linkId);
        $link->update(['is_active' => ! $link->is_active]);
    }

    public function moveUp(int $linkId): void
    {
        $this->swapOrder($linkId, 'up');
    }

    public function moveDown(int $linkId): void
    {
        $this->swapOrder($linkId, 'down');
    }

    private function swapOrder(int $linkId, string $direction): void
    {
        $current = ServiceLink::findOrFail($linkId);

        $neighbor = $direction === 'up'
            ? ServiceLink::where('order', '<', $current->order)->orderByDesc('order')->first()
            : ServiceLink::where('order', '>', $current->order)->orderBy('order')->first();

        if (! $neighbor) {
            return;
        }

        [$currentOrder, $neighborOrder] = [$current->order, $neighbor->order];

        $current->update(['order' => $neighborOrder]);
        $neighbor->update(['order' => $currentOrder]);
    }

    public function render()
    {
        return view('livewire.admin.services.service-link-index', [
            'links' => ServiceLink::orderBy('order')->get(),
            'editingLink' => $this->editingLinkId ? ServiceLink::find($this->editingLinkId) : null,
        ]);
    }
}
