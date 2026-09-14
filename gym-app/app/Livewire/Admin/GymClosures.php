<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Actions\GymClosure\CreateGymClosureAction;
use App\Actions\GymClosure\DeleteGymClosureAction;
use App\DTOs\GymClosureData;
use App\Enums\ClosureReason;
use App\Models\GymClosure;
use App\Models\Training;
use App\Support\LocalTime;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
final class GymClosures extends Component
{
    public string $starts_at_date = '';

    public string $starts_at_time = '00:00';

    public string $ends_at_date = '';

    public string $ends_at_time = '23:59';

    public string $reason = '';

    public string $note = '';

    /**
     * Two-step flow: the admin sees how many scheduled trainings would
     * be cancelled BEFORE committing (UX/UI Spec §9.11 — the one admin
     * action with real member-facing impact, so it earns a confirmation
     * step unlike most closure-management actions).
     */
    public bool $confirmingCreate = false;

    public ?int $previewAffectedCount = null;

    public ?int $confirmingDeleteId = null;

    public ?int $lastCancelledCount = null;

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'starts_at_date' => ['required', 'date'],
            'starts_at_time' => ['required', 'date_format:H:i'],
            'ends_at_date' => ['required', 'date'],
            'ends_at_time' => ['required', 'date_format:H:i'],
            'reason' => ['required', Rule::enum(ClosureReason::class)],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }

    private function buildData(): GymClosureData
    {
        return new GymClosureData(
            startsAt: LocalTime::toUtc($this->starts_at_date, $this->starts_at_time),
            endsAt: LocalTime::toUtc($this->ends_at_date, $this->ends_at_time),
            reason: ClosureReason::from($this->reason),
            note: $this->note !== '' ? $this->note : null,
        );
    }

    public function prepareConfirmation(): void
    {
        $this->lastCancelledCount = null;
        $this->validate();

        $data = $this->buildData();

        if (! $data->endsAt->gt($data->startsAt)) {
            $this->addError('ends_at_date', __('End must be after start.'));

            return;
        }

        // Read-only preview — the authoritative, locked count happens
        // inside CreateGymClosureAction's transaction on actual submit.
        $this->previewAffectedCount = Training::query()
            ->overlapping($data->startsAt, $data->endsAt)
            ->count();

        $this->confirmingCreate = true;
    }

    public function cancelConfirmation(): void
    {
        $this->confirmingCreate = false;
        $this->previewAffectedCount = null;
    }

    public function createClosure(CreateGymClosureAction $action): void
    {
        $result = $action->execute(auth()->user(), $this->buildData());

        $this->lastCancelledCount = $result['cancelledCount'];

        $this->reset([
            'starts_at_date', 'ends_at_date', 'reason', 'note',
            'confirmingCreate', 'previewAffectedCount',
        ]);
        $this->starts_at_time = '00:00';
        $this->ends_at_time = '23:59';

        unset($this->closures);
    }

    /**
     * @return Collection<int, GymClosure>
     */
    #[Computed]
    public function closures(): Collection
    {
        return GymClosure::query()
            ->with('creator')
            ->orderByDesc('starts_at')
            ->get();
    }

    public function confirmDelete(int $id): void
    {
        $this->confirmingDeleteId = $id;
    }

    public function cancelDelete(): void
    {
        $this->confirmingDeleteId = null;
    }

    public function deleteClosure(DeleteGymClosureAction $action): void
    {
        if (! $this->confirmingDeleteId) {
            return;
        }

        $closure = GymClosure::query()->findOrFail($this->confirmingDeleteId);
        $action->execute(auth()->user(), $closure);

        $this->confirmingDeleteId = null;
        unset($this->closures);
    }

    public function render(): View
    {
        return view('livewire.admin.gym-closures', [
            'reasons' => ClosureReason::cases(),
        ]);
    }
}
