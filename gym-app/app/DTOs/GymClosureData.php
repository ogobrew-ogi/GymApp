<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Enums\ClosureReason;
use Carbon\CarbonImmutable;

/**
 * UTC-normalized (Domain Model Spec §11.1), same pattern as TrainingData.
 */
final readonly class GymClosureData
{
    public function __construct(
        public CarbonImmutable $startsAt,
        public CarbonImmutable $endsAt,
        public ClosureReason $reason,
        public ?string $note = null,
    ) {}
}
