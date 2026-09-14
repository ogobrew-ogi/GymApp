<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ClosureReason;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class GymClosure extends Model
{
    /** @use HasFactory<\Database\Factories\GymClosureFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'starts_at',
        'ends_at',
        'reason',
        'note',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'reason' => ClosureReason::class,
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
     * Closures still relevant to future scheduling checks — past closures
     * no longer block anything (Domain Model Spec §4).
     *
     * @param  Builder<GymClosure>  $query
     * @return Builder<GymClosure>
     */
    public function scopeUpcomingOrActive(Builder $query): Builder
    {
        return $query->where('ends_at', '>', CarbonImmutable::now());
    }

    /**
     * Any closure (regardless of ends_at) whose window intersects
     * [$start, $end) — used to test a candidate training window.
     *
     * @param  Builder<GymClosure>  $query
     * @return Builder<GymClosure>
     */
    public function scopeOverlapping(
        Builder $query,
        CarbonImmutable $start,
        CarbonImmutable $end,
    ): Builder {
        return $query
            ->where('starts_at', '<', $end)
            ->where('ends_at', '>', $start);
    }
}
