<?php

namespace App\Livewire\Services;

use App\Models\ServiceLink;
use Livewire\Component;

class ServicesIndex extends Component
{
    public function render()
    {
        return view('livewire.services.services-index', [
            'links' => ServiceLink::where('is_active', true)->orderBy('order')->get(),
        ]);
    }
}
