<?php

declare(strict_types=1);

namespace App\Actions\User;

use App\Models\User;
use App\Services\UserManagementService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;

final class ResetUserPasswordAction
{
    public function __construct(
        private readonly UserManagementService $userManagement,
    ) {}

    public function execute(User $admin, User $target): string
    {
        Gate::forUser($admin)->authorize('update', $target);

        $temporaryPassword = $this->userManagement->generateTemporaryPassword();

        $target->forceFill([
            'password' => Hash::make($temporaryPassword),
            'must_change_password' => true,
        ])->save();

        return $temporaryPassword;
    }
}
