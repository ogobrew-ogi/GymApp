<?php

declare(strict_types=1);

namespace App\Actions\Training;

use App\DTOs\TrainingData;
use App\Enums\TrainingStatus;
use App\Models\Training;
use App\Models\User;
use App\Services\TrainingSchedulingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class CreateTrainingAction
{
    public function __construct(
        private readonly TrainingSchedulingService $scheduling,
    ) {}

    public function execute(User $owner, TrainingData $data): Training
    {
        Gate::forUser($owner)->authorize('create', Training::class);

        // Pure checks first, outside the transaction — no reason to hold
        // a lock while validating things that don't need one.
        $this->scheduling->assertValidWindow($data);
        $this->scheduling->assertGymOpen($data);

        return DB::transaction(function () use ($owner, $data): Training {
            // The overlap check and the insert happen inside the same
            // transaction, with the check taking a row lock, so no
            // second request can slip a conflicting training in between
            // (Domain Model Spec §11.2).
            $this->scheduling->assertNoOverlap($data);

            $training = Training::query()->create([
                'owner_id' => $owner->id,
                'date' => $data->startTime->toDateString(),
                'start_time' => $data->startTime,
                'end_time' => $data->endTime,
                'max_participants' => $data->maxParticipants,
                'note' => $data->note,
                'status' => TrainingStatus::Scheduled,
            ]);

            // The creator automatically becomes the first participant
            // (Project Brief, "Training") — never a separate join step.
            $training->trainingParticipants()->create([
                'user_id' => $owner->id,
                'joined_at' => now(),
            ]);

            return $training;
        });
    }
}
