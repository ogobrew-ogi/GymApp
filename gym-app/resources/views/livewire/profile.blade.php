<div class="max-w-md mx-auto px-4 pt-4 pb-8 space-y-4">
    <h1 class="text-xl font-semibold">Profile</h1>

    <div class="bg-white rounded-2xl shadow-sm p-4">
        <p class="font-medium">{{ auth()->user()->name }}</p>
        <p class="text-sm text-gray-500">{{ auth()->user()->username }}</p>
    </div>

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
