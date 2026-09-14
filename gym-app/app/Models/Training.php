<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TrainingStatus;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Training extends Model
{
    /** @use HasFactory<\Database\Factories\TrainingFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'owner_id',
        'date',
        'start_time',
        'end_time',
        'max_participants',
        'note',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'start_time' => 'datetime',
            'end_time' => 'datetime',
            'max_participants' => 'integer',
            'status' => TrainingStatus::class,
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * @return HasMany<TrainingParticipant, $this>
     */
    public function trainingParticipants(): HasMany
    {
        return $this->hasMany(TrainingParticipant::class);
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'training_participants')
            ->withPivot('joined_at')
            ->using(TrainingParticipant::class);
    }

    /**
     * Pure query composition — not a business decision. Callers (Services)
     * decide what "scheduled" means for a given operation; this scope just
     * saves repeating the same where clause everywhere.
     *
     * @param  Builder<Training>  $query
     * @return Builder<Training>
     */
    public function scopeScheduled(Builder $query): Builder
    {
        return $query->where('status', TrainingStatus::Scheduled);
    }

    /**
     * Any scheduled training whose window intersects [$start, $end).
     * Overlap test: existing.start < new.end AND existing.end > new.start
     * (Domain Model Spec §7, "any overlap is a conflict").
     *
     * Optionally excludes a given training id, needed when checking a
     * training against itself during an edit.
     *
     * @param  Builder<Training>  $query
     * @return Builder<Training>
     */
    public function scopeOverlapping(
        Builder $query,
        CarbonImmutable $start,
        CarbonImmutable $end,
        ?int $excludingId = null,
    ): Builder {
        return $query
            ->scheduled()
            ->where('start_time', '<', $end)
            ->where('end_time', '>', $start)
            ->when($excludingId, fn (Builder $q): Builder => $q->whereKeyNot($excludingId));
    }

    /**
     * @param  Builder<Training>  $query
     * @return Builder<Training>
     */
    public function scopeChronological(Builder $query): Builder
    {
        return $query->orderBy('start_time');
    }
}
