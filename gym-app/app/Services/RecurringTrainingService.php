<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\TrainingData;
use App\Enums\TrainingStatus;
use App\Models\GymClosure;
use App\Models\RecurringTraining;
use App\Models\Training;
use App\Support\LocalTime;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Turns an admin's weekly slot into real trainings for the 30-day
 * booking window, and keeps members from booking over that slot.
 */
final class RecurringTrainingService
{
    /** Same horizon members can book within. */
    public const int WINDOW_DAYS = 30;

    /**
     * Creates the trainings this slot still needs, from the day after it
     * was last generated up to the end of the booking window. Days the
     * gym is closed, or already taken by another training, are skipped.
     *
     * @return array{created: int, skipped: list<string>}
     */
    public function generate(RecurringTraining $rule): array
    {
        $today = CarbonImmutable::now(LocalTime::DISPLAY_TIMEZONE)->startOfDay();
        $until = $today->addDays(self::WINDOW_DAYS);

        $from = $rule->generated_until !== null
            ? CarbonImmutable::parse($rule->generated_until->toDateString(), LocalTime::DISPLAY_TIMEZONE)->addDay()
            : $today;

        if ($from->lt($today)) {
            $from = $today;
        }

        $created = 0;
        $skipped = [];

        for ($day = $from; $day->lte($until); $day = $day->addDay()) {
            if (! $rule->runsOn($day)) {
                continue;
            }

            $date = $day->toDateString();
            $start = LocalTime::toUtc($date, $rule->start_time);
            $end = LocalTime::toUtc($date, $rule->end_time);

            if ($start->lte(now())) {
                continue;
            }

            $made = DB::transaction(function () use ($rule, $date, $start, $end): bool {
                $closed = GymClosure::query()->upcomingOrActive()->overlapping($start, $end)->exists();
                $taken = Training::query()->overlapping($start, $end)->lockForUpdate()->exists();

                if ($closed || $taken) {
                    return false;
                }

                Training::query()->create([
                    'owner_id' => $rule->created_by,
                    'recurring_training_id' => $rule->id,
                    'date' => $date,
                    'start_time' => $start,
                    'end_time' => $end,
                    'max_participants' => $rule->max_participants,
                    'note' => $rule->note,
                    'status' => TrainingStatus::Scheduled,
                ]);

                return true;
            });

            if ($made) {
                $created++;
            } else {
                $skipped[] = $date;
            }
        }

        $rule->update(['generated_until' => $until->toDateString()]);

        return ['created' => $created, 'skipped' => $skipped];
    }

    public function generateAll(): int
    {
        $created = 0;

        RecurringTraining::query()->each(function (RecurringTraining $rule) use (&$created): void {
            $created += $this->generate($rule)['created'];
        });

        return $created;
    }

    /**
     * Stops a member's training from taking a reserved weekly slot,
     * including dates the slot hasn't been generated for yet.
     */
    public function assertSlotFree(TrainingData $data): void
    {
        $startLocal = LocalTime::toLocal($data->startTime);
        $endLocal = LocalTime::toLocal($data->endTime);

        // A training is at most 4 hours, so it touches one or two days.
        $days = array_unique([$startLocal->toDateString(), $endLocal->toDateString()]);

        foreach (RecurringTraining::query()->get() as $rule) {
            foreach ($days as $date) {
                if (! $rule->runsOn(CarbonImmutable::parse($date, LocalTime::DISPLAY_TIMEZONE))) {
                    continue;
                }

                $slotStart = LocalTime::toUtc($date, $rule->start_time);
                $slotEnd = LocalTime::toUtc($date, $rule->end_time);

                if ($data->startTime->lt($slotEnd) && $data->endTime->gt($slotStart)) {
                    throw ValidationException::withMessages([
                        'start_time' => __('This time is reserved for a regular training (:days, :start–:end).', [
                            'days' => $rule->weekdaysLabel(),
                            'start' => $rule->start_time,
                            'end' => $rule->end_time,
                        ]),
                    ]);
                }
            }
        }
    }

    /**
     * Removes the slot and its upcoming trainings. Trainings that already
     * ended are left to the normal purge.
     */
    public function delete(RecurringTraining $rule): int
    {
        return DB::transaction(function () use ($rule): int {
            $removed = $rule->trainings()->where('end_time', '>', now())->delete();
            $rule->delete();

            return $removed;
        });
    }
}
