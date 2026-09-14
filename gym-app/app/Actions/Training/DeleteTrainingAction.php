<?php

declare(strict_types=1);

namespace App\Actions\Training;

use App\Models\Training;
use App\Models\User;
use App\Services\TrainingLifecycleService;
use Illuminate\Support\Facades\Gate;

final class DeleteTrainingAction
{
    public function __construct(
        private readonly TrainingLifecycleService $lifecycle,
    ) {}

    public function execute(User $actor, Training $training): void
    {
        Gate::forUser($actor)->authorize('delete', $training);

        $this->lifecycle->delete($training);
    }
}
