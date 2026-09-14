<?php

declare(strict_types=1);

namespace App\Support;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Every conversion between the gym's single display timezone
 * (Europe/Sofia) and the UTC values stored in the database goes through
 * here — no Livewire component, Action, or Service converts timezones
 * on its own. This keeps the UTC-storage decision (Domain Model Spec
 * §11.1) enforceable in one place instead of relied-upon everywhere.
 */
final class LocalTime
{
    public const string DISPLAY_TIMEZONE = 'Europe/Sofia';

    /**
     * Parses a date + time pair as entered by the user (assumed to be in
     * Europe/Sofia local time) into a UTC CarbonImmutable ready for
     * storage.
     */
    public static function toUtc(string $date, string $time): CarbonImmutable
    {
        return CarbonImmutable::createFromFormat(
            'Y-m-d H:i',
            "{$date} {$time}",
            self::DISPLAY_TIMEZONE,
        )->utc();
    }

    /**
     * Converts a UTC-stored value into Europe/Sofia for display. Accepts
     * any Carbon-compatible instance so it can be called directly on
     * model attributes.
     */
    public static function toLocal(CarbonInterface $utc): CarbonImmutable
    {
        return CarbonImmutable::instance($utc)->setTimezone(self::DISPLAY_TIMEZONE);
    }
}
