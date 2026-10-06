<?php

namespace App\Livewire\Events;

use App\Models\Event;
use Livewire\Component;

/**
 * Página de eventos/agenda: lista os eventos publicados em ordem
 * cronológica de início.
 */
class EventsIndex extends Component
{
    public function render()
    {
        return view('livewire.events.events-index', [
            'events' => Event::where('is_published', true)->orderBy('starts_at')->get(),
        ]);
    }
}
