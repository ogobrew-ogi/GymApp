<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A message from an admin shown to every member on the Home screen.
 */
final class Announcement extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'message',
        'expires_at',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
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
     * @param  Builder<Announcement>  $query
     * @return Builder<Announcement>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where(
            fn (Builder $q): Builder => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()),
        );
    }
}
