<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Changes a user's own password and clears must_change_password.
 *
 * Used both for a voluntary change (Profile screen) and the forced
 * change after an admin-assigned temporary password (UX/UI Spec §9.9).
 */
final class ChangePasswordAction
{
    public function execute(User $user, string $currentPassword, string $newPassword): void
    {
        if (! Hash::check($currentPassword, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => __('That current password is not correct.'),
            ]);
        }

        $user->forceFill([
            'password' => Hash::make($newPassword),
            'must_change_password' => false,
        ])->save();

        // Sign the user out everywhere except the device making the
        // change, so an old password can't keep another session alive.
        DB::table(config('session.table', 'sessions'))
            ->where('user_id', $user->getKey())
            ->where('id', '!=', session()->getId())
            ->delete();
    }
}
