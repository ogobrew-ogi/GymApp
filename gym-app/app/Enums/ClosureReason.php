<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Reason a GymClosure was created (Domain Model Spec §5.3).
 *
 * Values taken directly from the Project Brief's "Gym Rules" section —
 * no additions or omissions.
 */
enum ClosureReason: string
{
    case Maintenance = 'maintenance';
    case Competition = 'competition';
    case Seminar = 'seminar';
    case Holiday = 'holiday';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Maintenance => 'Maintenance',
            self::Competition => 'Competition',
            self::Seminar => 'Seminar',
            self::Holiday => 'Holiday',
            self::Other => 'Other',
        };
    }
}
