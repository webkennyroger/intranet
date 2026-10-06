<?php

namespace App\Livewire\Birthdays;

use App\Models\User;
use Livewire\Component;

/**
 * Página de aniversariantes do mês: lista os usuários que fazem
 * aniversário no mês atual, ordenados pelo dia do mês.
 */
class BirthdaysIndex extends Component
{
    public function render()
    {
        return view('livewire.birthdays.birthdays-index', [
            'birthdays' => User::whereNotNull('birth_date')
                ->whereMonth('birth_date', now()->month)
                ->get()
                ->sortBy(fn ($user) => $user->birth_date->day)
                ->values(),
        ]);
    }
}
