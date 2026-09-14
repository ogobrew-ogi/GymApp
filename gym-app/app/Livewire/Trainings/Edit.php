<?php

declare(strict_types=1);

namespace App\Livewire\Trainings;

use App\Actions\Training\UpdateTrainingAction;
use App\DTOs\TrainingData;
use App\Exceptions\GymClosedException;
use App\Exceptions\TrainingOverlapException;
use App\Models\Training;
use App\Support\LocalTime;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
final class Edit extends Component
{
    use AuthorizesRequests;

    public Training $training;

    public string $date = '';

    public string $start_time = '';

    public string $end_time = '';

    public int $max_participants = 1;

    public string $note = '';

    /**
     * True once anyone besides the owner has joined — locks date/start/
     * end in the UI (Domain Model Spec §2), mirrored by field-level
     * validation in UpdateTrainingAction so this isn't just a UI-layer
     * restriction.
     */
    public bool $timeLocked = false;

    public ?int $conflictingTrainingId = null;

    public function mount(Training $training): void
    {
        $this->authorize('update', $training);

        $training->load('trainingParticipants');

        $this->training = $training;
        $this->date = LocalTime::toLocal($training->start_time)->toDateString();
        $this->start_time = LocalTime::toLocal($training->start_time)->format('H:i');
        $this->end_time = LocalTime::toLocal($training->end_time)->format('H:i');
        $this->max_participants = $training->max_participants;
        $this->note = (string) $training->note;
        $this->timeLocked = $training->trainingParticipants->count() > 1;
    }

    /**
     * @return array<string, list<string>>
     */
    protected function rules(): array
    {
        $minParticipants = max(1, $this->training->trainingParticipants->count());

        return [
            'date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
            'max_participants' => ['required', 'integer', "min:{$minParticipants}"],
            'note' => ['nullable', 'string', 'max:200'],
        ];
    }

    public function save(UpdateTrainingAction $action): void
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
            $action->execute(auth()->user(), $this->training, $data);
        } catch (TrainingOverlapException $e) {
            $this->conflictingTrainingId = $e->conflictingTraining->id;

            return;
        } catch (GymClosedException $e) {
            $this->addError('date', __('The gym is closed during that time (:reason). Choose another date.', [
                'reason' => $e->closure->reason->label(),
            ]));

            return;
        } catch (ValidationException $e) {
            foreach ($e->errors() as $field => $messages) {
                $this->addError($field, $messages[0]);
            }

            return;
        }

        $this->redirectRoute('trainings.show', $this->training, navigate: true);
    }

    public function render(): View
    {
        return view('livewire.trainings.edit', [
            'conflictingTraining' => $this->conflictingTrainingId
                ? Training::query()->with('owner')->find($this->conflictingTrainingId)
                : null,
        ]);
    }
}
