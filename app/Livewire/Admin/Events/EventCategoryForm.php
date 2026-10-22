<?php

namespace App\Livewire\Admin\Events;

use App\Models\Event;
use App\Models\EventCategory;
use App\Services\ActivityLogger;
use Livewire\Attributes\Validate;
use Livewire\Component;

/**
 * Formulário de criação/edição de uma categoria/tipo rápido de evento
 * (nome + cor), aberto em modal a partir da barra lateral do calendário.
 */
class EventCategoryForm extends Component
{
    public ?EventCategory $category = null;

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('required|regex:/^#[0-9A-Fa-f]{6}$/')]
    public string $color = '';

    public function mount(?EventCategory $category = null): void
    {
        if ($category) {
            $this->category = $category;
            $this->name = $category->name;
            $this->color = $category->color;
        } else {
            $this->color = Event::defaultColor();
        }
    }

    public function save(): void
    {
        abort_unless(auth()->user()->can($this->category ? 'events.update' : 'events.create'), 403);

        $this->validate();

        $data = ['name' => $this->name, 'color' => $this->color];

        if ($this->category) {
            $changes = ActivityLogger::diff($this->category, $data);
            $this->category->update($data);
            if ($changes !== []) {
                ActivityLogger::updated($this->category, $changes);
            }
            $this->dispatch('toast', title: 'Categoria', message: 'Categoria atualizada com sucesso.', variant: 'success');
        } else {
            $data['order'] = (EventCategory::max('order') ?? 0) + 1;
            $category = EventCategory::create($data);
            ActivityLogger::created($category);
            $this->dispatch('toast', title: 'Categoria', message: 'Categoria criada com sucesso.', variant: 'success');
        }

        $this->dispatch('event-category-saved');
        $this->reset(['name', 'color']);
    }

    public function render()
    {
        return view('livewire.admin.events.event-category-form');
    }
}
