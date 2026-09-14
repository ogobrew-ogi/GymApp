<?php

declare(strict_types=1);

namespace App\Actions\Training;

use App\Models\Training;
use App\Models\User;
use App\Services\TrainingLifecycleService;
use Illuminate\Support\Facades\Gate;

final class CancelTrainingAction
{
    public function __construct(
        private readonly TrainingLifecycleService $lifecycle,
    ) {}

    public function execute(User $actor, Training $training): Training
    {
        Gate::forUser($actor)->authorize('cancel', $training);

        return $this->lifecycle->cancel($training);
    }
}
