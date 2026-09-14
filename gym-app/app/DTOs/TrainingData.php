<?php

declare(strict_types=1);

namespace App\DTOs;

use Carbon\CarbonImmutable;

/**
 * Immutable transfer object for creating/updating a Training. Carries
 * already-parsed, UTC-normalized datetimes (Domain Model Spec §11.1) —
 * the Europe/Sofia <-> UTC conversion happens at the point this DTO is
 * constructed (from Livewire component input), not inside any Action.
 */
final readonly class TrainingData
{
    public function __construct(
        public CarbonImmutable $startTime,
        public CarbonImmutable $endTime,
        public int $maxParticipants,
        public ?string $note = null,
    ) {}
}
