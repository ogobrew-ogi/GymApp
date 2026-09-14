<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

/**
 * All abilities here are admin-only (Architecture Spec, "Administrator"
 * role). Delete and Disable are both permitted regardless of training
 * history, per the confirmed decision in the Domain Model Specification
 * §1/§6/§10 — this policy does not re-introduce that restriction.
 */
final class UserPolicy
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
     * Covers both "reset password" and "toggle active" — same admin-only
     * gate, no data-dependent restriction (unlike Training).
     */
    public function update(User $user, User $target): bool
    {
        return $user->is_admin;
    }

    /**
     * Not part of any approved document — a defensive guard against an
     * admin locking themselves out by deleting or disabling their own
     * account. This does not reintroduce the training-history
     * restriction you overrode; it's a narrower, different safety net.
     * Flagging it explicitly: remove the `$user->isNot($target)` check
     * below if you'd rather admins be able to act on their own account
     * too.
     */
    public function delete(User $user, User $target): bool
    {
        return $user->is_admin && $user->isNot($target);
    }
}
