<?php

declare(strict_types=1);

namespace App\Actions\User;

use App\Models\User;
use Illuminate\Support\Facades\Gate;

final class ToggleUserActiveAction
{
    public function execute(User $admin, User $target): User
    {
        Gate::forUser($admin)->authorize('update', $target);

        $target->update(['is_active' => ! $target->is_active]);

        return $target->refresh();
    }
}
