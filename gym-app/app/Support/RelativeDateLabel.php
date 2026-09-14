<?php

declare(strict_types=1);

namespace App\Support;

use Carbon\CarbonInterface;

/**
 * Formats a date the way the Home Screen groups trainings: "Today",
 * "Tomorrow", or an absolute date further out (UX/UI Spec §10).
 */
final class RelativeDateLabel
{
    public static function for(CarbonInterface $date): string
    {
        $today = $date->copy()->now($date->getTimezone())->startOfDay();
        $target = $date->copy()->startOfDay();

        return match (true) {
            $target->equalTo($today) => 'Today',
            $target->equalTo($today->copy()->addDay()) => 'Tomorrow',
            default => $target->isoFormat('ddd, MMM D'),
        };
    }
}
