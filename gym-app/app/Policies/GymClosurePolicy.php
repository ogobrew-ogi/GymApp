<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\GymClosure;
use App\Models\User;

final class GymClosurePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_admin;
    }

    public function create(User $user): bool
    {
        return $user->is_admin;
    }

    /**
     * Removing a future closure an admin created by mistake (UX/UI Spec
     * §9.11). Does not require the acting admin to be the original
     * creator — any admin may manage any closure, consistent with there
     * being no per-admin ownership concept anywhere else in the system.
     */
    public function delete(User $user, GymClosure $closure): bool
    {
        return $user->is_admin;
    }
}
