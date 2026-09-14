<?php

declare(strict_types=1);

namespace App\Actions\Training;

use App\Models\Training;
use App\Models\User;
use App\Services\TrainingLifecycleService;
use Illuminate\Support\Facades\Gate;

final class LeaveTrainingAction
{
    public function __construct(
        private readonly TrainingLifecycleService $lifecycle,
    ) {}

    public function execute(User $user, Training $training): void
    {
        Gate::forUser($user)->authorize('leave', $training);

        $this->lifecycle->leave($training, $user);
    }
}
