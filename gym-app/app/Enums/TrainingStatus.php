<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Training lifecycle state (Domain Model Spec §5.2).
 *
 * Trainings are created directly into Scheduled — there is no draft or
 * approval workflow. Completed and Cancelled are both terminal; nothing
 * transitions out of them.
 */
enum TrainingStatus: string
{
    case Scheduled = 'scheduled';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    /**
     * Human-readable label for UI display (UX/UI Spec status badges).
     */
    public function label(): string
    {
        return match ($this) {
            self::Scheduled => 'Scheduled',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
        };
    }

    /**
     * Only Scheduled trainings participate in overlap/capacity/join checks.
     */
    public function isActive(): bool
    {
        return $this === self::Scheduled;
    }
}
