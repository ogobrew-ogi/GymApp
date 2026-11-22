<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A weekly time slot reserved by an admin. Trainings are generated from
 * it (see RecurringTrainingService) and members join them as usual.
 */
final class RecurringTraining extends Model
{
    public const array WEEKDAY_LABELS = [
        1 => 'Mon',
        2 => 'Tue',
        3 => 'Wed',
        4 => 'Thu',
        5 => 'Fri',
        6 => 'Sat',
        7 => 'Sun',
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'weekdays',
        'start_time',
        'end_time',
        'max_participants',
        'note',
        'created_by',
        'generated_until',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'weekdays' => 'array',
            'max_participants' => 'integer',
            'generated_until' => 'immutable_date',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<Training, $this>
     */
    public function trainings(): HasMany
    {
        return $this->hasMany(Training::class);
    }

    public function runsOn(CarbonImmutable $localDate): bool
    {
        return in_array($localDate->isoWeekday(), array_map('intval', $this->weekdays), true);
    }

    public function weekdaysLabel(): string
    {
        $days = array_map('intval', $this->weekdays);
        sort($days);

        return implode(', ', array_map(fn (int $d): string => self::WEEKDAY_LABELS[$d], $days));
    }
}
