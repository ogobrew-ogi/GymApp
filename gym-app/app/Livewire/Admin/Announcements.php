<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Models\Announcement;
use App\Support\LocalTime;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Admin screen for messages shown to every member on Home.
 */
#[Layout('components.layouts.app')]
final class Announcements extends Component
{
    public string $body = '';

    /** Optional local date (Y-m-d); the message shows through that day. */
    public string $expires_on = '';

    public bool $saved = false;

    public ?int $confirmingDeleteId = null;

    public function mount(): void
    {
        abort_unless(auth()->user()->is_admin, 403);
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:500'],
            'expires_on' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:today'],
        ];
    }

    public function save(): void
    {
        abort_unless(auth()->user()->is_admin, 403);

        $this->body = trim($this->body);
        $this->validate();

        Announcement::query()->create([
            'message' => $this->body,
            'expires_at' => $this->expires_on !== ''
                ? LocalTime::toUtc($this->expires_on, '23:59')
                : null,
            'created_by' => auth()->id(),
        ]);

        $this->reset(['body', 'expires_on']);
        $this->saved = true;
        unset($this->announcements);
    }

    /**
     * @return Collection<int, Announcement>
     */
    #[Computed]
    public function announcements(): Collection
    {
        return Announcement::query()->with('creator')->latest()->get();
    }

    public function confirmDelete(int $id): void
    {
        $this->confirmingDeleteId = $id;
    }

    public function cancelDelete(): void
    {
        $this->confirmingDeleteId = null;
    }

    public function delete(): void
    {
        abort_unless(auth()->user()->is_admin, 403);

        if ($this->confirmingDeleteId) {
            Announcement::query()->whereKey($this->confirmingDeleteId)->delete();
        }

        $this->confirmingDeleteId = null;
        $this->saved = false;
        unset($this->announcements);
    }

    public function render(): View
    {
        return view('livewire.admin.announcements');
    }
}
