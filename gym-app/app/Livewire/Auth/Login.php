<?php

declare(strict_types=1);

namespace App\Livewire\Auth;

use App\Actions\Auth\AttemptLoginAction;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.guest')]
final class Login extends Component
{
    public string $username = '';

    public string $password = '';

    public bool $remember = false;

    /**
     * @return array<string, list<string>>
     */
    protected function rules(): array
    {
        return [
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ];
    }

    public function login(AttemptLoginAction $action): void
    {
        $this->validate();

        // Login throttling per the accepted engineering recommendation
        // (Architecture Spec §12) — keyed by username + IP.
        $throttleKey = mb_strtolower($this->username).'|'.request()->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, maxAttempts: 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            throw ValidationException::withMessages([
                'username' => __('Too many attempts. Please try again in :seconds seconds.', ['seconds' => $seconds]),
            ]);
        }

        try {
            $action->execute($this->username, $this->password, $this->remember);
        } catch (ValidationException $e) {
            RateLimiter::hit($throttleKey, decaySeconds: 60);

            throw $e;
        }

        RateLimiter::clear($throttleKey);

        session()->regenerate();

        $this->redirectRoute('trainings.index', navigate: true);
    }

    public function render(): View
    {
        return view('livewire.auth.login');
    }
}
