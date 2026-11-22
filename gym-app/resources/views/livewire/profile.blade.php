<div class="max-w-md mx-auto px-4 pt-4 pb-8 space-y-4">
    <h1 class="text-xl font-semibold">Profile</h1>

    <div class="bg-white rounded-2xl shadow-sm p-4">
        <p class="font-medium">{{ auth()->user()->name }}</p>
        <p class="text-sm text-gray-500">{{ auth()->user()->username }}</p>
    </div>

    <form wire:submit="savePhone" class="bg-white rounded-2xl shadow-sm p-4 space-y-3">
        <div>
            <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Phone (optional)</label>
            <input
                type="tel"
                id="phone"
                wire:model="phone"
                autocomplete="tel"
                inputmode="tel"
                placeholder="+359 888 123 456"
                class="w-full min-h-[48px] rounded-lg border-gray-300 px-4"
            >
            <p class="mt-1 text-xs text-gray-500">Everyone in the gym can see it, so they can call you.</p>
            @error('phone') <p class="mt-1 text-sm text-status-full">{{ $message }}</p> @enderror
            @if ($phoneSaved)
                <p class="mt-1 text-sm text-status-open">Saved.</p>
            @endif
        </div>
        <button type="submit" wire:loading.attr="disabled" class="w-full min-h-[44px] rounded-lg border border-gray-300 text-sm font-medium">
            Save Phone
        </button>
    </form>

    <div class="bg-white rounded-2xl shadow-sm divide-y divide-gray-100">
        <a href="{{ route('password.change') }}" wire:navigate class="block px-4 py-4 text-sm font-medium">
            Change Password
        </a>

        @can('viewAny', App\Models\User::class)
            <a href="{{ route('admin.users.index') }}" wire:navigate class="block px-4 py-4 text-sm font-medium">
                User Management
            </a>
        @endcan

        @can('viewAny', App\Models\GymClosure::class)
            <a href="{{ route('admin.recurring.index') }}" wire:navigate class="block px-4 py-4 text-sm font-medium">
                Regular Trainings
            </a>
            <a href="{{ route('admin.announcements.index') }}" wire:navigate class="block px-4 py-4 text-sm font-medium">
                Announcements
            </a>
            <a href="{{ route('admin.closures.index') }}" wire:navigate class="block px-4 py-4 text-sm font-medium">
                Gym Closures
            </a>
        @endcan

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="w-full text-left px-4 py-4 text-sm font-medium text-status-full">
                Log Out
            </button>
        </form>
    </div>
</div>
