<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Attempts to authenticate a user by username/password.
 *
 * Kept as a single-purpose Action (not inline in the Livewire component)
 * so the credential/active-account logic is unit-testable in isolation
 * and reusable if a second entry point (e.g. an API) is ever added.
 */
final class AttemptLoginAction
{
    public function execute(string $username, string $password, bool $remember = false): User
    {
        // Phone keyboards often add a trailing space or capitalise the
        // first letter, so the username is trimmed and matched
        // case-insensitively. The password is still matched exactly.
        $user = User::query()
            ->whereRaw('lower(username) = ?', [self::normalize($username)])
            ->first();

        // Deliberately generic message regardless of which check fails
        // (unknown username vs. wrong password) to avoid revealing
        // account existence.
        if (! $user instanceof User || ! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'username' => __('These credentials do not match our records.'),
            ]);
        }

        if (! $user->is_active) {
            throw ValidationException::withMessages([
                'username' => __('Your account is currently inactive. Please contact the gym admin.'),
            ]);
        }

        Auth::login($user, $remember);

        return $user;
    }

    public static function normalize(string $username): string
    {
        return mb_strtolower(trim($username));
    }
}
