<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\GymClosureData;
use App\Models\Training;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

final class GymClosureService
{
    public function assertValidWindow(GymClosureData $data): void
    {
        if (! $data->endsAt->gt($data->startsAt)) {
            throw ValidationException::withMessages([
                'ends_at' => __('End must be after start.'),
            ]);
        }
    }

    /**
     * Every currently Scheduled training whose window intersects the
     * closure — locked, so this must be called inside the same
     * transaction that performs the cancellations (Domain Model Spec §5,
     * "at write time, not a background job").
     *
     * @return Collection<int, Training>
     */
    public function findAffectedScheduledTrainings(GymClosureData $data): Collection
    {
        return Training::query()
            ->overlapping($data->startsAt, $data->endsAt)
            ->lockForUpdate()
            ->get();
    }
}
