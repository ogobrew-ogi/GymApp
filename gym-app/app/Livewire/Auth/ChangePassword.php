<?php

declare(strict_types=1);

namespace App\Livewire\Auth;

use App\Actions\Auth\ChangePasswordAction;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.guest')]
final class ChangePassword extends Component
{
    public string $current_password = '';

    public string $password = '';

    public string $password_confirmation = '';

    /**
     * @return array<string, list<string>>
     */
    protected function rules(): array
    {
        return [
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    public function save(ChangePasswordAction $action): void
    {
        $this->validate();

        $action->execute(auth()->user(), $this->current_password, $this->password);

        $this->redirectRoute('trainings.index', navigate: true);
    }

    public function render(): View
    {
        return view('livewire.auth.change-password');
    }
}
