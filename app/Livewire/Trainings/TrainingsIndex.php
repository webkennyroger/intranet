<?php

namespace App\Livewire\Trainings;

use App\Models\Training;
use Livewire\Component;

/**
 * Página de capacitações: lista os treinamentos publicados, mais
 * recentes primeiro.
 */
class TrainingsIndex extends Component
{
    public function render()
    {
        return view('livewire.trainings.trainings-index', [
            'trainings' => Training::where('is_published', true)->latest()->get(),
        ]);
    }
}
