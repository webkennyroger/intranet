<?php

namespace App\Livewire\Admin\Services;

use App\Models\ServiceLink;
use App\Services\ActivityLogger;
use Livewire\Attributes\Validate;
use Livewire\Component;

/**
 * Formulário admin de criação/edição dos links de serviço exibidos na
 * barra lateral (título, ícone, URL de destino e se está ativo).
 */
class ServiceLinkForm extends Component
{
    public ?ServiceLink $link = null;

    #[Validate('required|string|max:255')]
    public string $title = '';

    #[Validate('required|regex:/^[a-z0-9-]+$/')]
    public string $icon = '';

    #[Validate('required|url|max:2048')]
    public string $url = '';

    public bool $isActive = true;

    public function mount(?ServiceLink $link = null): void
    {
        if ($link) {
            $this->link = $link;
            $this->title = $link->title;
            $this->icon = $link->icon;
            $this->url = $link->url;
            $this->isActive = $link->is_active;
        }
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'title' => $this->title,
            'icon' => $this->icon,
            'url' => $this->url,
            'is_active' => $this->isActive,
        ];

        if ($this->link) {
            $changes = ActivityLogger::diff($this->link, $data);
            $this->link->update($data);
            if ($changes !== []) {
                ActivityLogger::updated($this->link, $changes);
            }
            $this->dispatch('toast', title: 'Serviço', message: 'Serviço atualizado com sucesso.', variant: 'success');
        } else {
            $data['order'] = (ServiceLink::max('order') ?? 0) + 1;
            $link = ServiceLink::create($data);
            ActivityLogger::created($link);
            $this->dispatch('toast', title: 'Serviço', message: 'Serviço criado com sucesso.', variant: 'success');
        }

        $this->dispatch('service-link-saved');
        $this->reset(['title', 'icon', 'url']);
        $this->isActive = true;
    }

    public function render()
    {
        return view('livewire.admin.services.service-link-form');
    }
}
