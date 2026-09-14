<?php

declare(strict_types=1);

namespace App\Livewire\Trainings;

use App\Actions\Training\CancelTrainingAction;
use App\Actions\Training\DeleteTrainingAction;
use App\Actions\Training\JoinTrainingAction;
use App\Actions\Training\LeaveTrainingAction;
use App\Models\Training;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
final class Show extends Component
{
    public Training $training;

    /**
     * Single lightweight confirmation step for Cancel/Delete (UX/UI Spec
     * §9.6) — not a separate screen, just a toggled in-place state.
     */
    public bool $confirmingCancel = false;

    public bool $confirmingDelete = false;

    public function mount(Training $training): void
    {
        $this->training = $training->load(['owner', 'trainingParticipants.user']);
    }

    /**
     * Named `joinTraining`, not `join` — `join` collides with
     * Array.prototype.join on Livewire/Alpine's `$wire` proxy, which
     * silently swallows the call client-side without ever reaching the
     * server (no request, no error — just a no-op).
     */
    public function joinTraining(JoinTrainingAction $action): void
    {
        try {
            $action->execute(auth()->user(), $this->training);
        } catch (ValidationException $e) {
            $this->addError('training', $e->getMessage());

            return;
        } catch (AuthorizationException) {
            // Policy denied it for a data-dependent reason (full, already
            // joined, no longer scheduled) rather than a coding error —
            // most likely a stale page racing another member's action.
            $this->addError('training', __('This training is no longer available.'));
            $this->refreshTraining();

            return;
        }

        $this->refreshTraining();
    }

    public function leave(LeaveTrainingAction $action): void
    {
        try {
            $action->execute(auth()->user(), $this->training);
        } catch (ValidationException $e) {
            $this->addError('training', $e->getMessage());

            return;
        } catch (AuthorizationException) {
            $this->addError('training', __('This training is no longer available.'));
            $this->refreshTraining();

            return;
        }

        $this->refreshTraining();
    }

    public function confirmCancel(): void
    {
        $this->confirmingCancel = true;
    }

    public function cancel(CancelTrainingAction $action): void
    {
        try {
            $action->execute(auth()->user(), $this->training);
        } catch (ValidationException $e) {
            $this->addError('training', $e->getMessage());

            return;
        }

        $this->confirmingCancel = false;
        $this->refreshTraining();
    }

    public function confirmDelete(): void
    {
        $this->confirmingDelete = true;
    }

    public function delete(DeleteTrainingAction $action): void
    {
        try {
            $action->execute(auth()->user(), $this->training);
        } catch (ValidationException $e) {
            $this->addError('training', $e->getMessage());

            return;
        }

        $this->redirectRoute('trainings.index', navigate: true);
    }

    private function refreshTraining(): void
    {
        $this->training = $this->training
            ->fresh(['owner', 'trainingParticipants.user']);
    }

    public function render(): View
    {
        return view('livewire.trainings.show');
    }
}
