<?php

declare(strict_types=1);

namespace App\Actions\Training;

use App\DTOs\TrainingData;
use App\Models\Training;
use App\Models\User;
use App\Services\TrainingSchedulingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final class UpdateTrainingAction
{
    public function __construct(
        private readonly TrainingSchedulingService $scheduling,
    ) {}

    public function execute(User $actor, Training $training, TrainingData $data): Training
    {
        Gate::forUser($actor)->authorize('update', $training);

        $participantCount = $training->trainingParticipants()->count();
        $isSolo = $participantCount === 1;

        if ($isSolo) {
            // Full edit allowed — same validation as creation.
            $this->scheduling->assertValidWindow($data);
            $this->scheduling->assertGymOpen($data);
        } else {
            // Once others have joined: date/start/end are locked, only
            // note and max_participants remain editable, and
            // max_participants can never drop below the current count
            // (Domain Model Spec §2, UX/UI Spec §9.4).
            $timeChanged = ! $data->startTime->equalTo($training->start_time)
                || ! $data->endTime->equalTo($training->end_time);

            if ($timeChanged) {
                throw ValidationException::withMessages([
                    'start_time' => __("The time can't be changed once someone else has joined."),
                ]);
            }

            if ($data->maxParticipants < $participantCount) {
                throw ValidationException::withMessages([
                    'max_participants' => __('Cannot be lower than the current number of participants.'),
                ]);
            }
        }

        return DB::transaction(function () use ($training, $data, $isSolo): Training {
            if ($isSolo) {
                $this->scheduling->assertNoOverlap($data, excludingId: $training->id);
            }

            $training->update([
                'date' => $data->startTime->toDateString(),
                'start_time' => $data->startTime,
                'end_time' => $data->endTime,
                'max_participants' => $data->maxParticipants,
                'note' => $data->note,
            ]);

            return $training->refresh();
        });
    }
}
