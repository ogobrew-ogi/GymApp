<?php

declare(strict_types=1);

namespace App\Livewire\Trainings;

use App\Enums\TrainingStatus;
use App\Models\Training;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Read-only reference list of completed/cancelled trainings (UX/UI Spec
 * §9.7) — no actions, no filters, nothing to manage. A "Load more" button
 * grows the window rather than full pagination machinery, per the spec's
 * explicit preference for the simpler option.
 */
#[Layout('components.layouts.app')]
final class History extends Component
{
    private const int PER_PAGE = 20;

    public int $perPage = self::PER_PAGE;

    /**
     * @return Collection<int, Training>
     */
    #[Computed]
    public function trainings(): Collection
    {
        return Training::query()
            ->whereIn('status', [TrainingStatus::Completed, TrainingStatus::Cancelled])
            ->with(['owner', 'trainingParticipants'])
            ->orderByDesc('start_time')
            ->take($this->perPage)
            ->get();
    }

    #[Computed]
    public function hasMore(): bool
    {
        return Training::query()
            ->whereIn('status', [TrainingStatus::Completed, TrainingStatus::Cancelled])
            ->count() > $this->perPage;
    }

    public function loadMore(): void
    {
        $this->perPage += self::PER_PAGE;
        unset($this->trainings, $this->hasMore);
    }

    public function render(): View
    {
        return view('livewire.trainings.history');
    }
}
