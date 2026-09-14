<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

final class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;

    use Notifiable;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'password',
        'is_admin',
        'is_active',
        'must_change_password',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'is_active' => 'boolean',
            'must_change_password' => 'boolean',
        ];
    }

    /**
     * Domain-vocabulary role, derived from is_admin — never independently
     * stored (Domain Model Spec §5.1).
     */
    public function role(): UserRole
    {
        return UserRole::fromIsAdmin($this->is_admin);
    }

    /**
     * Trainings this user owns (created). Cascade-deletes with the user
     * per the accepted decision — see trainings migration.
     *
     * @return HasMany<Training, $this>
     */
    public function ownedTrainings(): HasMany
    {
        return $this->hasMany(Training::class, 'owner_id');
    }

    /**
     * Trainings this user participates in (including their own owned
     * ones, since the owner is always also a participant).
     *
     * @return BelongsToMany<Training, $this>
     */
    public function trainings(): BelongsToMany
    {
        return $this->belongsToMany(Training::class, 'training_participants')
            ->withPivot('joined_at')
            ->using(TrainingParticipant::class);
    }

    /**
     * Gym closures this user (an admin) has created.
     *
     * @return HasMany<GymClosure, $this>
     */
    public function createdGymClosures(): HasMany
    {
        return $this->hasMany(GymClosure::class, 'created_by');
    }
}
