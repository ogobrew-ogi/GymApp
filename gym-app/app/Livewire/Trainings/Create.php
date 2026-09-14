<?php

declare(strict_types=1);

namespace App\Livewire\Trainings;

use App\Actions\Training\CreateTrainingAction;
use App\Actions\Training\JoinTrainingAction;
use App\DTOs\TrainingData;
use App\Exceptions\GymClosedException;
use App\Exceptions\TrainingOverlapException;
use App\Models\Training;
use App\Support\LocalTime;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Throwable;

#[Layout('components.layouts.app')]
final class Create extends Component
{
    public string $date = '';

    public string $start_time = '';

    public string $end_time = '';

    public int $max_participants = 10;

    public string $note = '';

    /**
     * Set instead of a plain validation error when the requested window
     * overlaps an existing training — swaps the Save button for a "Join
     * this training instead" prompt (UX/UI Spec §9.3, the most important
     * conflict moment in the app).
     */
    public ?int $conflictingTrainingId = null;

    public function mount(): void
    {
        $this->date = $this->resolveInitialDate();
    }

    /**
     * Pre-fills from a `?date=` query param — used by the Home screen's
     * date filter so "+ Create Training" while viewing a specific day
     * opens straight into that day instead of defaulting to today.
     * Falls back to today on anything missing/malformed; the actual
     * `after_or_equal:today` rule still guards the real submission.
     */
    private function resolveInitialDate(): string
    {
        $requested = request()->query('date');

        if (is_string($requested)) {
            try {
                return CarbonImmutable::createFromFormat('Y-m-d', $requested, LocalTime::DISPLAY_TIMEZONE)
                    ->toDateString();
            } catch (Throwable) {
                // fall through to today's default below
            }
        }

        return now('Europe/Sofia')->toDateString();
    }

    /**
     * @return array<string, list<string>>
     */
    protected function rules(): array
    {
        return [
            'date' => ['required', 'date', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
            'max_participants' => ['required', 'integer', 'min:1'],
            'note' => ['nullable', 'string', 'max:200'],
        ];
    }

    public function save(CreateTrainingAction $action): void
    {
        $this->conflictingTrainingId = null;
        $this->validate();

        $data = new TrainingData(
            startTime: LocalTime::toUtc($this->date, $this->start_time),
            endTime: LocalTime::toUtc($this->date, $this->end_time),
            maxParticipants: $this->max_participants,
            note: $this->note !== '' ? $this->note : null,
        );

        try {
            $training = $action->execute(auth()->user(), $data);
        } catch (TrainingOverlapException $e) {
            $this->conflictingTrainingId = $e->conflictingTraining->id;

            return;
        } catch (GymClosedException $e) {
            $this->addError('date', __('The gym is closed during that time (:reason). Choose another date.', [
                'reason' => $e->closure->reason->label(),
            ]));

            return;
        } catch (ValidationException $e) {
            $this->applyValidationException($e);

            return;
        }

        $this->redirectRoute('trainings.show', $training, navigate: true);
    }

    public function joinConflictingInstead(JoinTrainingAction $action): void
    {
        if (! $this->conflictingTrainingId) {
            return;
        }

        $training = Training::query()->findOrFail($this->conflictingTrainingId);

        try {
            $action->execute(auth()->user(), $training);
        } catch (ValidationException $e) {
            $this->applyValidationException($e);

            return;
        }

        $this->redirectRoute('trainings.show', $training, navigate: true);
    }

    private function applyValidationException(ValidationException $e): void
    {
        foreach ($e->errors() as $field => $messages) {
            $this->addError($field, $messages[0]);
        }
    }

    public function render(): View
    {
        return view('livewire.trainings.create', [
            'conflictingTraining' => $this->conflictingTrainingId
                ? Training::query()->with('owner')->find($this->conflictingTrainingId)
                : null,
        ]);
    }
}
