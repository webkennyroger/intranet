<?php

namespace App\Livewire\Birthdays;

use App\Models\BirthdayBanner;
use Livewire\Component;

/**
 * Página de aniversariantes do mês: exibe o banner comemorativo
 * daquele mês, cadastrado no admin (se houver um publicado).
 */
class BirthdaysIndex extends Component
{
    public function render()
    {
        return view('livewire.birthdays.birthdays-index', [
            'banner' => BirthdayBanner::where('month', now()->month)
                ->where('is_published', true)
                ->first(),
        ]);
    }
}
