<?php

namespace App\Livewire\Admin\Birthdays;

use App\Models\BirthdayBanner;
use App\Services\ActivityLogger;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Tela admin "Banners de Aniversariantes": lista os 12 meses do ano (um
 * banner no máximo por mês), permitindo enviar, editar, publicar/
 * despublicar e excluir a arte de cada um.
 */
class BirthdayBannerIndex extends Component
{
    public ?int $editingBannerId = null;

    public ?int $prefillMonth = null;

    #[On('birthday-banner-saved')]
    public function refreshList(): void
    {
        $this->editingBannerId = null;
        $this->prefillMonth = null;
        $this->dispatch('close-modal', 'birthday-banner-form');
    }

    public function edit(int $bannerId): void
    {
        $this->editingBannerId = $bannerId;
        $this->prefillMonth = null;
    }

    /**
     * Abre o formulário de criação já com o mês daquela linha preenchido —
     * não grava nada até o usuário enviar uma imagem e salvar.
     */
    public function createForMonth(int $month): void
    {
        $this->editingBannerId = null;
        $this->prefillMonth = $month;
    }

    public function closeForm(): void
    {
        $this->editingBannerId = null;
        $this->prefillMonth = null;
    }

    public function togglePublish(int $bannerId): void
    {
        abort_unless(auth()->user()->can('birthday-banners.update'), 403);

        $banner = BirthdayBanner::findOrFail($bannerId);
        $willBePublished = ! $banner->is_published;
        $banner->update(['is_published' => $willBePublished]);
        $willBePublished ? ActivityLogger::activated($banner) : ActivityLogger::deactivated($banner);
        $this->dispatch('toast', title: 'Banner de Aniversariantes', message: $willBePublished ? 'Banner publicado com sucesso.' : 'Banner despublicado.', variant: $willBePublished ? 'success' : 'warning');
    }

    public function delete(int $bannerId): void
    {
        abort_unless(auth()->user()->can('birthday-banners.delete'), 403);

        $banner = BirthdayBanner::findOrFail($bannerId);
        $banner->delete();
        ActivityLogger::deleted($banner);
        $this->dispatch('toast', title: 'Banner de Aniversariantes', message: 'Banner excluído com sucesso.', variant: 'danger');
    }

    public function render()
    {
        $banners = BirthdayBanner::orderBy('month')->get()->keyBy('month');

        $months = collect(BirthdayBanner::monthNames())->map(fn (string $name, int $month) => [
            'month' => $month,
            'name' => $name,
            'banner' => $banners->get($month),
        ])->values();

        return view('livewire.admin.birthdays.birthday-banner-index', [
            'months' => $months,
            'editingBanner' => $this->editingBannerId ? BirthdayBanner::find($this->editingBannerId) : null,
            'totalBanners' => $banners->count(),
        ])->layout('layouts.admin', ['title' => 'Banners de Aniversariantes']);
    }
}
