<?php

declare(strict_types=1);

namespace App\Livewire\Trainings;

use App\Actions\Training\JoinTrainingAction;
use App\Actions\Training\LeaveTrainingAction;
use App\Models\Training;
use App\Support\LocalTime;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
final class TrainingList extends Component
{
    /**
     * Optional single-day filter (Y-m-d). Empty means the default full
     * chronological view. UX/UI Spec §10 originally ruled out any
     * date-picker-as-navigation on this screen ("the list scrolls;
     * that's the only navigation method for time") — explicitly
     * overridden per the project owner's confirmed decision once the
     * list grew long enough that scrolling stopped being enough.
     */
    public string $selectedDate = '';

    public string $minSelectableDate = '';

    public string $maxSelectableDate = '';

    public function mount(): void
    {
        $today = CarbonImmutable::now(LocalTime::DISPLAY_TIMEZONE);

        $this->minSelectableDate = $today->toDateString();
        // Mirrors TrainingSchedulingService::MAX_ADVANCE_DAYS — nothing
        // can be scheduled further out, so nothing further out is ever
        // useful to jump to.
        $this->maxSelectableDate = $today->addDays(30)->toDateString();
    }

    /**
     * @return Collection<int, Training>
     */
    #[Computed]
    public function trainings(): Collection
    {
        return Training::query()
            ->scheduled()
            ->chronological()
            ->with(['owner', 'trainingParticipants'])
            ->when(
                $this->selectedDate !== '',
                // Not a plain `where('date', ...)` — the `date` column is
                // stored with a full "Y-m-d H:i:s" timestamp regardless
                // of its `date` cast (Eloquent's date/datetime casts
                // don't change the storage format, only how the value
                // reads back), so an exact string match would silently
                // never hit. whereDate() correctly truncates to the
                // calendar day on both sides of the comparison.
                fn (Builder $q): Builder => $q->whereDate('date', $this->selectedDate),
            )
            ->get();
    }

    public function clearDateFilter(): void
    {
        $this->selectedDate = '';
    }

    /**
     * Named `joinTraining`, not `join` — `join` collides with
     * Array.prototype.join on Livewire/Alpine's `$wire` proxy, which
     * silently swallows the call client-side without ever reaching the
     * server (no request, no error — just a no-op).
     */
    public function joinTraining(int $trainingId): void
    {
        $training = Training::query()->findOrFail($trainingId);

        try {
            app(JoinTrainingAction::class)->execute(auth()->user(), $training);
        } catch (ValidationException $e) {
            $this->addError('training', $e->getMessage());

            return;
        } catch (AuthorizationException) {
            // Policy denied it for a data-dependent reason (full, already
            // joined, no longer scheduled) rather than a coding error —
            // most likely a stale page racing another member's action.
            $this->addError('training', __('This training is no longer available.'));
            unset($this->trainings);

            return;
        }

        unset($this->trainings);
    }

    public function leave(int $trainingId): void
    {
        $training = Training::query()->findOrFail($trainingId);

        try {
            app(LeaveTrainingAction::class)->execute(auth()->user(), $training);
        } catch (ValidationException $e) {
            $this->addError('training', $e->getMessage());

            return;
        } catch (AuthorizationException) {
            $this->addError('training', __('This training is no longer available.'));
            unset($this->trainings);

            return;
        }

        unset($this->trainings);
    }

    public function render(): View
    {
        return view('livewire.trainings.training-list');
    }
}
