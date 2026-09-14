<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\TrainingStatus;
use App\Models\Training;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Owns every Training state transition (Domain Model Spec §4, §8). Each
 * method assumes the caller (an Action) has already authorized the
 * attempt via TrainingPolicy — these checks are a second, defensive
 * layer against races (e.g. two people tapping Join within the same
 * millisecond), not a substitute for authorization.
 */
final class TrainingLifecycleService
{
    /**
     * Locks the training row for the duration of the check+write, so two
     * concurrent joins can never push participants past max_participants
     * (Domain Model Spec §11.2 — same transactional-locking principle as
     * the overlap check, applied here to capacity instead).
     */
    public function join(Training $training, User $user): void
    {
        DB::transaction(function () use ($training, $user): void {
            $locked = Training::query()->whereKey($training->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== TrainingStatus::Scheduled) {
                throw ValidationException::withMessages([
                    'training' => __('This training is no longer available.'),
                ]);
            }

            $alreadyJoined = $locked->trainingParticipants()->where('user_id', $user->id)->exists();

            if ($alreadyJoined) {
                throw ValidationException::withMessages([
                    'training' => __("You're already in this training."),
                ]);
            }

            $participantCount = $locked->trainingParticipants()->count();

            if ($participantCount >= $locked->max_participants) {
                throw ValidationException::withMessages([
                    'training' => __('This training is full.'),
                ]);
            }

            $locked->trainingParticipants()->create([
                'user_id' => $user->id,
                'joined_at' => now(),
            ]);
        });
    }

    /**
     * The owner-exemption is enforced by TrainingPolicy::leave, not here
     * — this method trusts it was already checked.
     */
    public function leave(Training $training, User $user): void
    {
        $training->trainingParticipants()->where('user_id', $user->id)->delete();
    }

    /**
     * Sets Cancelled (Domain Model Spec §4 state machine). Used both for
     * an owner-initiated cancel and, separately, for the gym-closure
     * cascade (which calls this same method per Training, not a copy of
     * this logic — see CreateGymClosureAction).
     */
    public function cancel(Training $training): Training
    {
        $training->update(['status' => TrainingStatus::Cancelled]);

        return $training->refresh();
    }

    /**
     * Hard delete — only ever valid while the owner is the sole
     * participant (re-checked here under lock, defensively, even though
     * TrainingPolicy::delete already gated the attempt).
     */
    public function delete(Training $training): void
    {
        DB::transaction(function () use ($training): void {
            $locked = Training::query()->whereKey($training->id)->lockForUpdate()->firstOrFail();

            if ($locked->trainingParticipants()->count() > 1) {
                throw ValidationException::withMessages([
                    'training' => __("Can't delete once others have joined — cancel instead."),
                ]);
            }

            $locked->delete();
        });
    }

    /**
     * Called by the trainings:complete-past scheduled command (Domain
     * Model Spec §11.5 sequencing note; Architecture Spec §7). Idempotent
     * by construction — only ever touches rows still Scheduled.
     */
    public function completePast(): int
    {
        return Training::query()
            ->scheduled()
            ->where('end_time', '<', now())
            ->update(['status' => TrainingStatus::Completed]);
    }
}
