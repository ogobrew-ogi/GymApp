<div class="max-w-md mx-auto px-4 pt-4 pb-8 space-y-4">
    <a href="{{ route('profile') }}" wire:navigate class="text-sm text-gray-500">&larr; Back</a>

    <h1 class="text-xl font-semibold">User Management</h1>

    @error('user')
        <div class="bg-red-50 text-status-full text-sm rounded-lg p-3">{{ $message }}</div>
    @enderror

    {{-- Temporary password, shown once, persistent panel (not auto-dismissing) --}}
    @if ($revealedPassword)
        <div class="bg-white rounded-2xl shadow-sm p-5 space-y-3 border border-brand/30">
            <p class="text-sm text-gray-700">
                Temporary password for <strong>{{ $revealedForUsername }}</strong>:
            </p>
            <p class="text-lg font-mono bg-gray-50 rounded-lg p-3 select-all">{{ $revealedPassword }}</p>
            <p class="text-xs text-gray-500">
                This won't be shown again — relay it to the member directly. They'll be asked to set
                their own password on first login.
            </p>
            <button wire:click="dismissRevealedPassword" class="w-full min-h-[44px] rounded-lg border border-gray-300 text-sm font-medium">
                Done
            </button>
        </div>
    @endif

    @if (! $showAddForm && ! $revealedPassword)
        <button
            wire:click="$set('showAddForm', true)"
            class="w-full min-h-[48px] rounded-lg bg-brand text-white font-medium"
        >
            + Add User
        </button>
    @endif

    @if ($showAddForm)
        <form wire:submit="addUser" class="bg-white rounded-2xl shadow-sm p-5 space-y-4">
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                <input type="text" id="name" wire:model="name" class="w-full min-h-[48px] rounded-lg border-gray-300 px-4">
                @error('name') <p class="mt-1 text-sm text-status-full">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="username" class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                <input type="text" id="username" wire:model="username" class="w-full min-h-[48px] rounded-lg border-gray-300 px-4">
                @error('username') <p class="mt-1 text-sm text-status-full">{{ $message }}</p> @enderror
            </div>

            <label class="flex items-center gap-2 text-sm text-gray-700">
                <input type="checkbox" wire:model="is_admin" class="rounded border-gray-300">
                Administrator
            </label>

            <div class="flex gap-2">
                <button type="button" wire:click="$set('showAddForm', false)" class="flex-1 min-h-[48px] rounded-lg border border-gray-300 text-sm font-medium">
                    Cancel
                </button>
                <button type="submit" wire:loading.attr="disabled" wire:target="addUser" class="flex-1 min-h-[48px] rounded-lg bg-brand text-white text-sm font-medium">
                    Create
                </button>
            </div>
        </form>
    @endif

    <div class="space-y-3">
        @foreach ($this->users as $user)
            <div class="bg-white rounded-2xl shadow-sm p-4 space-y-3" wire:key="user-{{ $user->id }}">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="font-medium">
                            {{ $user->name }}
                            @if ($user->is_admin)
                                <span class="text-xs text-brand font-normal">· admin</span>
                            @endif
                        </p>
                        <p class="text-sm text-gray-500">{{ $user->username }}</p>
                    </div>
                    <span class="text-xs font-medium px-2 py-1 rounded-full {{ $user->is_active ? 'bg-green-50 text-status-open' : 'bg-gray-100 text-status-cancelled' }}">
                        {{ $user->is_active ? 'Active' : 'Disabled' }}
                    </span>
                </div>

                @if ($confirmingDeleteUserId === $user->id)
                    <div class="border border-status-full/30 rounded-lg p-3 space-y-2">
                        <p class="text-sm text-gray-700">
                            Deleting {{ $user->name }} will also delete
                            <strong>{{ $deleteImpactOwnedTrainings }}</strong> training(s) they organized
                            and remove them from <strong>{{ $deleteImpactOtherParticipations }}</strong>
                            other training(s). This can't be undone.
                        </p>
                        <div class="flex gap-2">
                            <button wire:click="cancelDelete" class="flex-1 min-h-[44px] rounded-lg border border-gray-300 text-sm font-medium">
                                Go Back
                            </button>
                            <button wire:click="deleteUser" wire:loading.attr="disabled" class="flex-1 min-h-[44px] rounded-lg bg-status-full text-white text-sm font-medium">
                                Confirm Delete
                            </button>
                        </div>
                    </div>
                @else
                    <div class="flex gap-2 text-sm">
                        <button wire:click="toggleActive({{ $user->id }})" class="flex-1 min-h-[40px] rounded-lg border border-gray-300 font-medium">
                            {{ $user->is_active ? 'Disable' : 'Enable' }}
                        </button>
                        <button wire:click="resetPassword({{ $user->id }})" class="flex-1 min-h-[40px] rounded-lg border border-gray-300 font-medium">
                            Reset Password
                        </button>
                        <button wire:click="confirmDelete({{ $user->id }})" class="flex-1 min-h-[40px] rounded-lg border border-status-full text-status-full font-medium">
                            Delete
                        </button>
                    </div>
                @endif
            </div>
        @endforeach
    </div>
</div>
