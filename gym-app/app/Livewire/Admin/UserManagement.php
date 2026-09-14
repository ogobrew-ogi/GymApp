<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Actions\User\CreateUserAction;
use App\Actions\User\DeleteUserAction;
use App\Actions\User\ResetUserPasswordAction;
use App\Actions\User\ToggleUserActiveAction;
use App\DTOs\NewUserData;
use App\Models\User;
use App\Services\UserManagementService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
final class UserManagement extends Component
{
    public bool $showAddForm = false;

    public string $name = '';

    public string $username = '';

    public bool $is_admin = false;

    public ?int $confirmingDeleteUserId = null;

    public ?int $deleteImpactOwnedTrainings = null;

    public ?int $deleteImpactOtherParticipations = null;

    /**
     * Shown exactly once (UX/UI Spec §9.10) — never persisted, never
     * logged, cleared as soon as the admin dismisses the panel.
     */
    public ?string $revealedPassword = null;

    public ?string $revealedForUsername = null;

    /**
     * @return Collection<int, User>
     */
    #[Computed]
    public function users(): Collection
    {
        return User::query()->orderBy('name')->get();
    }

    /**
     * @return array<string, list<string>>
     */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'unique:users,username'],
        ];
    }

    public function addUser(CreateUserAction $action): void
    {
        $this->validate();

        $result = $action->execute(auth()->user(), new NewUserData(
            name: $this->name,
            username: $this->username,
            isAdmin: $this->is_admin,
        ));

        $this->revealedPassword = $result['temporaryPassword'];
        $this->revealedForUsername = $result['user']->username;

        $this->reset(['name', 'username', 'is_admin', 'showAddForm']);
        unset($this->users);
    }

    public function toggleActive(int $userId, ToggleUserActiveAction $action): void
    {
        $target = User::query()->findOrFail($userId);

        $action->execute(auth()->user(), $target);

        unset($this->users);
    }

    public function resetPassword(int $userId, ResetUserPasswordAction $action): void
    {
        $target = User::query()->findOrFail($userId);

        $this->revealedPassword = $action->execute(auth()->user(), $target);
        $this->revealedForUsername = $target->username;
    }

    public function dismissRevealedPassword(): void
    {
        $this->reset(['revealedPassword', 'revealedForUsername']);
    }

    /**
     * Computes the cascade-impact counts shown in the confirmation copy
     * BEFORE the admin commits (UX/UI Spec §9.10, accepted revision) —
     * same figures the Action itself will log after the fact.
     */
    public function confirmDelete(int $userId): void
    {
        $target = User::query()->findOrFail($userId);
        $impact = app(UserManagementService::class)->cascadeImpact($target);

        $this->confirmingDeleteUserId = $userId;
        $this->deleteImpactOwnedTrainings = $impact['ownedTrainings'];
        $this->deleteImpactOtherParticipations = $impact['otherParticipations'];
    }

    public function cancelDelete(): void
    {
        $this->reset(['confirmingDeleteUserId', 'deleteImpactOwnedTrainings', 'deleteImpactOtherParticipations']);
    }

    public function deleteUser(DeleteUserAction $action): void
    {
        if (! $this->confirmingDeleteUserId) {
            return;
        }

        $target = User::query()->findOrFail($this->confirmingDeleteUserId);

        try {
            $action->execute(auth()->user(), $target);
        } catch (AuthorizationException) {
            // Self-lockout guard in UserPolicy::delete (flagged earlier).
            $this->addError('user', __("You can't delete your own account."));
            $this->cancelDelete();

            return;
        }

        $this->cancelDelete();
        unset($this->users);
    }

    public function render(): View
    {
        return view('livewire.admin.user-management');
    }
}
