<?php

declare(strict_types=1);

namespace App\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
final class Profile extends Component
{
    /** Optional; shown to every member in training participant lists. */
    public string $phone = '';

    public bool $phoneSaved = false;

    public function mount(): void
    {
        $this->phone = (string) auth()->user()->phone;
    }

    public function savePhone(): void
    {
        $this->phoneSaved = false;
        $this->phone = trim($this->phone);

        $this->validate([
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^\+?[0-9][0-9 ()\-]{5,28}$/'],
        ], [
            'phone.regex' => __('Enter a valid phone number, e.g. +359 888 123 456.'),
        ]);

        auth()->user()->update(['phone' => $this->phone !== '' ? $this->phone : null]);

        $this->phoneSaved = true;
    }

    public function render(): View
    {
        return view('livewire.profile');
    }
}
