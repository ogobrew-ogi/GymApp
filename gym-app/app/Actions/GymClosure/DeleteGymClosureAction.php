<?php

declare(strict_types=1);

namespace App\Actions\GymClosure;

use App\Models\GymClosure;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

final class DeleteGymClosureAction
{
    public function execute(User $admin, GymClosure $closure): void
    {
        Gate::forUser($admin)->authorize('delete', $closure);

        // Deliberately no cascade: trainings already cancelled because of
        // this closure stay cancelled — removing the closure never
        // resurrects them (Domain Model Spec §4, "not a feature this
        // system offers").
        $closure->delete();
    }
}
