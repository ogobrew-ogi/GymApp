<?php

declare(strict_types=1);

namespace App\Actions\User;

use App\Models\User;
use App\Services\UserManagementService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

final class DeleteUserAction
{
    public function __construct(
        private readonly UserManagementService $userManagement,
    ) {}

    public function execute(User $admin, User $target): void
    {
        Gate::forUser($admin)->authorize('delete', $target);

        // Counted before deletion — the cascade (DB foreign keys,
        // Domain Model Spec §6) removes the rows, but the log entry
        // needs the number that will disappear (accepted recommendation,
        // §11.6).
        $impact = $this->userManagement->cascadeImpact($target);

        Log::info('User deleted; owned trainings and other participations cascaded.', [
            'deleted_user_id' => $target->id,
            'deleted_by' => $admin->id,
            'owned_trainings_deleted' => $impact['ownedTrainings'],
            'other_participations_removed' => $impact['otherParticipations'],
        ]);

        $target->delete();
    }
}
