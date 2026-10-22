<?php

namespace App\Livewire\Admin\Birthdays;

use App\Models\BirthdayBanner;
use App\Services\ActivityLogger;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Formulário admin de criação/edição de banners de Aniversariantes (mês e
 * imagem) — modal aberto a partir de BirthdayBannerIndex.
 */
class BirthdayBannerForm extends Component
{
    use WithFileUploads;

    public ?BirthdayBanner $banner = null;

    #[Validate('required|integer|min:1|max:12')]
    public string $month = '';

    public $image = null;

    public ?string $existingImage = null;

    public function mount(?BirthdayBanner $banner = null, ?int $prefillMonth = null): void
    {
        if ($banner) {
            $this->banner = $banner;
            $this->month = (string) $banner->month;
            $this->existingImage = $banner->image_path;
        } elseif ($prefillMonth) {
            $this->month = (string) $prefillMonth;
        }
    }

    public function save(): void
    {
        abort_unless(auth()->user()->can($this->banner ? 'birthday-banners.update' : 'birthday-banners.create'), 403);

        $this->validate([
            'month' => [
                'required',
                'integer',
                'min:1',
                'max:12',
                Rule::unique('birthday_banners', 'month')->ignore($this->banner?->id),
            ],
            'image' => $this->banner ? 'nullable|image|max:4096' : 'required|image|max:4096',
        ]);

        $data = [
            'month' => (int) $this->month,
        ];

        if ($this->image) {
            $data['image_path'] = $this->image->store('birthdays', 'public');
        }

        if ($this->banner) {
            $changes = ActivityLogger::diff($this->banner, $data);
            $this->banner->update($data);
            if ($changes !== []) {
                ActivityLogger::updated($this->banner, $changes);
            }
            $this->dispatch('toast', title: 'Banner de Aniversariantes', message: 'Banner atualizado com sucesso.', variant: 'success');
        } else {
            // Banner novo entra publicado — despublicar é uma ação separada
            // na tabela (togglePublish), não um campo deste formulário.
            $banner = BirthdayBanner::create($data + ['is_published' => true]);
            ActivityLogger::created($banner);
            $this->dispatch('toast', title: 'Banner de Aniversariantes', message: 'Banner criado com sucesso.', variant: 'success');
        }

        $this->dispatch('birthday-banner-saved');
        $this->reset(['month', 'image', 'existingImage']);
    }

    public function render()
    {
        return view('livewire.admin.birthdays.birthday-banner-form', [
            'monthNames' => BirthdayBanner::monthNames(),
        ]);
    }
}
