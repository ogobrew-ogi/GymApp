<?php

declare(strict_types=1);

namespace App\Actions\User;

use App\DTOs\NewUserData;
use App\Models\User;
use App\Services\UserManagementService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;

final class CreateUserAction
{
    public function __construct(
        private readonly UserManagementService $userManagement,
    ) {}

    /**
     * @return array{user: User, temporaryPassword: string}
     */
    public function execute(User $admin, NewUserData $data): array
    {
        Gate::forUser($admin)->authorize('create', User::class);

        $temporaryPassword = $this->userManagement->generateTemporaryPassword();

        $user = User::query()->create([
            'name' => $data->name,
            'username' => $data->username,
            'password' => Hash::make($temporaryPassword),
            'is_admin' => $data->isAdmin,
            'is_active' => true,
            // Forces the change-password screen on first login
            // (UX/UI Spec §9.9) since this password was never chosen by
            // the member themselves.
            'must_change_password' => true,
        ]);

        // Returned, not stored or logged in plaintext anywhere — the
        // caller (Livewire component) displays it once per UX/UI Spec
        // §9.10 and must not persist it beyond that render.
        return ['user' => $user, 'temporaryPassword' => $temporaryPassword];
    }
}
