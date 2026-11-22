<?php

declare(strict_types=1);

namespace App\Livewire\Trainings;

use App\Actions\Training\JoinTrainingAction;
use App\Actions\Training\LeaveTrainingAction;
use App\Enums\TrainingStatus;
use App\Models\Announcement;
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
     * chronological view. Picked from the day strip at the top of Home.
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
     * Upcoming trainings. Cancelled ones stay listed (with a badge) until
     * their end time so people who joined can see it was called off;
     * after that every training is deleted.
     *
     * @return Collection<int, Training>
     */
    #[Computed]
    public function trainings(): Collection
    {
        return $this->upcoming()
            ->chronological()
            ->with(['owner', 'trainingParticipants'])
            ->when(
                $this->selectedDate !== '',
                // whereDate(), not where('date', …): the column holds a
                // full "Y-m-d H:i:s" string, so an exact match never hits.
                fn (Builder $q): Builder => $q->whereDate('date', $this->selectedDate),
            )
            ->get();
    }

    /**
     * Days shown in the strip, each flagged when something is scheduled.
     *
     * @return list<array{date: string, weekday: string, day: string, month: string, hasTrainings: bool, isToday: bool}>
     */
    #[Computed]
    public function days(): array
    {
        $busy = $this->upcoming()
            ->where('status', TrainingStatus::Scheduled)
            ->get(['start_time'])
            ->map(fn (Training $t): string => LocalTime::toLocal($t->start_time)->toDateString())
            ->unique()
            ->flip();

        $today = CarbonImmutable::parse($this->minSelectableDate, LocalTime::DISPLAY_TIMEZONE);
        $days = [];

        for ($i = 0; $i <= 30; $i++) {
            $day = $today->addDays($i);
            $key = $day->toDateString();

            $days[] = [
                'date' => $key,
                'weekday' => $i === 0 ? 'Today' : $day->format('D'),
                'day' => $day->format('j'),
                'month' => $day->format('M'),
                'hasTrainings' => $busy->has($key),
                'isToday' => $i === 0,
            ];
        }

        return $days;
    }

    /**
     * @return Collection<int, Announcement>
     */
    #[Computed]
    public function announcements(): Collection
    {
        return Announcement::query()->active()->with('creator')->latest()->get();
    }

    public function selectDate(string $date): void
    {
        $this->selectedDate = $this->selectedDate === $date ? '' : $date;
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

    /**
     * Not yet ended, scheduled or cancelled. Doesn't rely on the purge
     * job having run, so an ended training disappears right away.
     *
     * @return Builder<Training>
     */
    private function upcoming(): Builder
    {
        return Training::query()
            ->whereIn('status', [TrainingStatus::Scheduled, TrainingStatus::Cancelled])
            ->where('end_time', '>', now());
    }

    public function render(): View
    {
        return view('livewire.trainings.training-list');
    }
}
