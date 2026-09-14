<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\TrainingStatus;
use App\Models\Training;
use App\Models\User;
use Carbon\CarbonImmutable;

/**
 * Authorization only. Field-level edit restrictions (which specific
 * fields are editable once others have joined) are enforced in the
 * Form Request/Action for update, not here — this policy only decides
 * whether the attempt is allowed at all. Gym-closure blocking on create
 * is data-dependent and checked in the Form Request, not here (see
 * Architecture Spec §6).
 */
final class TrainingPolicy
{
    /**
     * Every authenticated, active member may view all trainings —
     * transparency is a core principle, nothing is hidden between
     * members (Architecture Spec, "Transparency").
     */
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    public function view(User $user, Training $training): bool
    {
        return $user->is_active;
    }

    /**
     * Any active member may attempt to create a training. Whether the
     * requested window is actually free and the gym isn't closed is
     * validated at write time, not here.
     */
    public function create(User $user): bool
    {
        return $user->is_active;
    }

    public function update(User $user, Training $training): bool
    {
        return $user->is_active
            && $user->id === $training->owner_id
            && $training->status === TrainingStatus::Scheduled;
    }

    /**
     * Only when the owner is the sole participant (Domain Model Spec §3,
     * §5.2). Once anyone else has joined, "cancel" is the only path.
     */
    public function delete(User $user, Training $training): bool
    {
        return $user->is_active
            && $user->id === $training->owner_id
            && $training->status === TrainingStatus::Scheduled
            && $training->trainingParticipants()->count() === 1;
    }

    /**
     * Custom ability (not a default CRUD verb) — mutually exclusive with
     * delete: only once someone else has joined.
     */
    public function cancel(User $user, Training $training): bool
    {
        return $user->is_active
            && $user->id === $training->owner_id
            && $training->status === TrainingStatus::Scheduled
            && $training->trainingParticipants()->count() > 1;
    }

    public function join(User $user, Training $training): bool
    {
        if (! $user->is_active || $training->status !== TrainingStatus::Scheduled) {
            return false;
        }

        $alreadyJoined = $training->trainingParticipants()
            ->where('user_id', $user->id)
            ->exists();

        if ($alreadyJoined) {
            return false;
        }

        $isFull = $training->trainingParticipants()->count() >= $training->max_participants;

        if ($isFull) {
            return false;
        }

        // Defensive check: given the one-training-at-a-time invariant,
        // this should rarely if ever trigger, but a user's existing
        // scheduled training could theoretically still overlap this one
        // if it was edited after they joined it (Domain Model Spec §3).
        return ! $user->trainings()
            ->overlapping(
                CarbonImmutable::instance($training->start_time),
                CarbonImmutable::instance($training->end_time),
                excludingId: $training->id,
            )
            ->exists();
    }

    /**
     * The owner can never leave their own training (Architecture §5.4)
     * — their only exits are delete (solo) or cancel (with others).
     */
    public function leave(User $user, Training $training): bool
    {
        if ($user->id === $training->owner_id) {
            return false;
        }

        return $training->status === TrainingStatus::Scheduled
            && $training->trainingParticipants()
                ->where('user_id', $user->id)
                ->exists();
    }
}
