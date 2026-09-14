<?php

declare(strict_types=1);

namespace App\Actions\GymClosure;

use App\DTOs\GymClosureData;
use App\Models\GymClosure;
use App\Models\User;
use App\Services\GymClosureService;
use App\Services\TrainingLifecycleService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

final class CreateGymClosureAction
{
    public function __construct(
        private readonly GymClosureService $closures,
        private readonly TrainingLifecycleService $lifecycle,
    ) {}

    /**
     * @return array{closure: GymClosure, cancelledCount: int}
     */
    public function execute(User $admin, GymClosureData $data): array
    {
        Gate::forUser($admin)->authorize('create', GymClosure::class);

        $this->closures->assertValidWindow($data);

        return DB::transaction(function () use ($admin, $data): array {
            $closure = GymClosure::query()->create([
                'starts_at' => $data->startsAt,
                'ends_at' => $data->endsAt,
                'reason' => $data->reason,
                'note' => $data->note,
                'created_by' => $admin->id,
            ]);

            // Cancellation happens atomically with closure creation, not
            // as a background job (Domain Model Spec §5). Each training
            // is cancelled through the same TrainingLifecycleService
            // method an owner-initiated cancel would use — no duplicated
            // cancellation logic.
            $affected = $this->closures->findAffectedScheduledTrainings($data);

            foreach ($affected as $training) {
                $this->lifecycle->cancel($training);
            }

            $cancelledCount = $affected->count();

            // Plain application log, not a domain/audit table (accepted
            // recommendation, Architecture Spec §12 / Domain Model §11.6).
            Log::info('Gym closure created; scheduled trainings cancelled.', [
                'closure_id' => $closure->id,
                'created_by' => $admin->id,
                'cancelled_training_ids' => $affected->pluck('id')->all(),
                'cancelled_count' => $cancelledCount,
            ]);

            return ['closure' => $closure, 'cancelledCount' => $cancelledCount];
        });
    }
}
