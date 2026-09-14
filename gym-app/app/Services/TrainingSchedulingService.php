<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\TrainingData;
use App\Exceptions\GymClosedException;
use App\Exceptions\TrainingOverlapException;
use App\Models\GymClosure;
use App\Models\Training;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

/**
 * Stateless validation logic for scheduling a Training window. Every
 * rule here traces directly to a numbered Domain Model Spec rule — no
 * rule exists here that wasn't already approved.
 */
final class TrainingSchedulingService
{
    private const int MIN_DURATION_MINUTES = 30;

    private const int MAX_DURATION_MINUTES = 4 * 60;

    private const int MAX_ADVANCE_DAYS = 30;

    /**
     * Duration bounds + booking-window checks (Domain Model Spec §2).
     * Pure, no DB access — safe to call outside a transaction.
     */
    public function assertValidWindow(TrainingData $data): void
    {
        if (! $data->endTime->gt($data->startTime)) {
            throw ValidationException::withMessages([
                'end_time' => __('End time must be after start time.'),
            ]);
        }

        $minutes = $data->startTime->diffInMinutes($data->endTime);

        if ($minutes < self::MIN_DURATION_MINUTES) {
            throw ValidationException::withMessages([
                'end_time' => __('Trainings must be at least 30 minutes long.'),
            ]);
        }

        if ($minutes > self::MAX_DURATION_MINUTES) {
            throw ValidationException::withMessages([
                'end_time' => __('Trainings can be up to 4 hours long.'),
            ]);
        }

        if ($data->startTime->gt(CarbonImmutable::now()->addDays(self::MAX_ADVANCE_DAYS))) {
            throw ValidationException::withMessages([
                'date' => __('You can only schedule up to 30 days in advance.'),
            ]);
        }
    }

    /**
     * Gym-closure check (Domain Model Spec §4). Data-dependent, so this
     * intentionally lives here rather than in TrainingPolicy::create.
     */
    public function assertGymOpen(TrainingData $data): void
    {
        $closure = GymClosure::query()
            ->upcomingOrActive()
            ->overlapping($data->startTime, $data->endTime)
            ->first();

        if ($closure instanceof GymClosure) {
            throw new GymClosedException($closure);
        }
    }

    /**
     * The central "one training at a time" invariant (Domain Model Spec
     * §2, §7). Must be called from inside the same transaction that
     * performs the write (Architecture Spec §12 / Domain Model §11.2) —
     * the lockForUpdate() here only closes the race window when a
     * transaction actually wraps it.
     */
    public function assertNoOverlap(TrainingData $data, ?int $excludingId = null): void
    {
        $conflict = Training::query()
            ->overlapping($data->startTime, $data->endTime, $excludingId)
            ->lockForUpdate()
            ->first();

        if ($conflict instanceof Training) {
            throw new TrainingOverlapException($conflict);
        }
    }
}
