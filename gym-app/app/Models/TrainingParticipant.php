<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\TrainingParticipantFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

final class TrainingParticipant extends Pivot
{
    /** @use HasFactory<TrainingParticipantFactory> */
    use HasFactory;

    /**
     * Real, id-keyed table (not a composite-key anonymous pivot) — see
     * Domain Model Spec §3 for why a surrogate key was chosen.
     */
    public $incrementing = true;

    /**
     * Table only has `joined_at` — no created_at/updated_at columns
     * (Eloquent's Pivot base class defaults timestamps on).
     */
    public $timestamps = false;

    protected $table = 'training_participants';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'training_id',
        'user_id',
        'joined_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'joined_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Training, $this>
     */
    public function training(): BelongsTo
    {
        return $this->belongsTo(Training::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Whether this participation row belongs to the training's owner.
     * Derived, not stored — see Domain Model Spec §5.4 on why no
     * `is_owner` column exists.
     */
    public function isOwner(): bool
    {
        return $this->user_id === $this->training?->owner_id;
    }
}
