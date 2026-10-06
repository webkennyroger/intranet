<?php

namespace App\Livewire\Auth;

use App\Enums\UserStatus;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Attributes\Validate;
use Livewire\Component;

/**
 * Formulário de login público. Valida credenciais, aplica rate limiting
 * contra tentativas repetidas, bloqueia contas inativas/bloqueadas e,
 * se tudo passar, autentica o usuário e redireciona para o dashboard.
 */
class Login extends Component
{
    #[Validate('required|email')]
    public string $email = '';

    #[Validate('required|min:6')]
    public string $password = '';

    public bool $remember = false;

    public function submit(): void
    {
        $this->validate();

        if (RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            $seconds = RateLimiter::availableIn($this->throttleKey());
            $this->addError('email', "Muitas tentativas de login. Tente novamente em {$seconds} segundos.");

            return;
        }

        if (! Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            RateLimiter::hit($this->throttleKey());
            $this->addError('email', 'E-mail ou senha inválidos.');

            return;
        }

        $user = Auth::user();

        if (! $user->isActive()) {
            Auth::logout();
            RateLimiter::hit($this->throttleKey());
            $this->addError('email', $this->statusDeniedMessage($user->status));

            return;
        }

        $user->forceFill(['last_login_at' => now()])->save();

        RateLimiter::clear($this->throttleKey());
        session()->regenerate();

        $this->redirect(route('dashboard'), navigate: true);
    }

    private function statusDeniedMessage(UserStatus $status): string
    {
        return match ($status) {
            UserStatus::Blocked => 'Sua conta foi bloqueada. Contate um administrador.',
            default => 'Não é possível acessar o sistema com esta conta.',
        };
    }

    private function throttleKey(): string
    {
        // Combina e-mail + IP para limitar tentativas por conta e por origem,
        // evitando que um atacante use e-mails diferentes do mesmo IP.
        return Str::transliterate(Str::lower($this->email).'|'.request()->ip());
    }

    public function render()
    {
        return view('livewire.auth.login')->layout('layouts.guest');
    }
}
