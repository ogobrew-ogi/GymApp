<?php

declare(strict_types=1);

namespace App\View\Components;

use App\Models\Training;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\Component;

/**
 * Presentational only — every value here is either read directly off the
 * model or a policy check used purely to decide what to render. No
 * write, no business decision. See UX/UI Spec §11 for the element-by-
 * element contract this implements.
 */
final class TrainingCard extends Component
{
    public bool $isOwner;

    public bool $isJoined;

    public bool $isFull;

    public int $spotsRemaining;

    public bool $canJoin;

    public bool $canLeave;

    public function __construct(
        public readonly Training $training,
    ) {
        $user = auth()->user();
        $participantCount = $training->trainingParticipants->count();

        $this->isOwner = $user->id === $training->owner_id;
        $this->isJoined = $training->trainingParticipants->contains('user_id', $user->id);
        $this->isFull = $participantCount >= $training->max_participants;
        $this->spotsRemaining = max(0, $training->max_participants - $participantCount);
        $this->canJoin = Gate::forUser($user)->allows('join', $training);
        $this->canLeave = Gate::forUser($user)->allows('leave', $training);
    }

    public function render(): View
    {
        return view('components.training-card');
    }
}
