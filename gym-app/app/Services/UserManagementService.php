<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\TrainingParticipant;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

final class UserManagementService
{
    /**
     * A readable, sufficiently random temporary password an admin can
     * relay to a member directly (Architecture Spec, "Password Reset" —
     * fully manual, no email infrastructure).
     */
    public function generateTemporaryPassword(): string
    {
        return Str::password(length: 12, symbols: false);
    }

    /**
     * Counts what deleting this user would cascade-remove — used both to
     * show the cascade-impact confirmation copy before the admin commits
     * (UX/UI Spec §9.10, accepted revision) and in the log entry after
     * (Domain Model Spec §11.6).
     *
     * @return array{ownedTrainings: int, otherParticipations: int}
     */
    public function cascadeImpact(User $user): array
    {
        return [
            'ownedTrainings' => $user->ownedTrainings()->count(),

            // Trainings this user merely joined (not owns) — deleting
            // them frees these slots for others rather than deleting
            // the training itself.
            'otherParticipations' => TrainingParticipant::query()
                ->where('user_id', $user->id)
                ->whereHas('training', fn (Builder $query): Builder => $query->where('owner_id', '!=', $user->id))
                ->count(),
        ];
    }
}
