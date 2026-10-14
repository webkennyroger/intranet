<?php

namespace App\Livewire\Admin\Birthdays;

use App\Models\BirthdayBanner;
use App\Services\ActivityLogger;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Tela admin "Banners de Aniversariantes": lista os banners comemorativos
 * cadastrados por mês, permitindo editar, publicar/despublicar e excluir.
 */
class BirthdayBannerIndex extends Component
{
    public ?int $editingBannerId = null;

    #[On('birthday-banner-saved')]
    public function refreshList(): void
    {
        $this->editingBannerId = null;
        $this->dispatch('close-modal', 'birthday-banner-form');
    }

    public function edit(int $bannerId): void
    {
        $this->editingBannerId = $bannerId;
    }

    public function closeForm(): void
    {
        $this->editingBannerId = null;
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
        $banners = BirthdayBanner::orderBy('month')->get();

        return view('livewire.admin.birthdays.birthday-banner-index', [
            'banners' => $banners,
            'editingBanner' => $this->editingBannerId ? BirthdayBanner::find($this->editingBannerId) : null,
            'totalBanners' => $banners->count(),
            'publishedBanners' => $banners->where('is_published', true)->count(),
        ])->layout('layouts.admin', ['title' => 'Banners de Aniversariantes']);
    }
}
