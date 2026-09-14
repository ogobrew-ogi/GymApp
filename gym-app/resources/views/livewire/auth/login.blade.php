<div class="w-full max-w-sm">
    <div class="bg-white rounded-2xl shadow-sm p-6">
        <h1 class="text-xl font-semibold text-center mb-6">{{ config('app.name') }}</h1>

        <form wire:submit="login" class="space-y-4">
            <div>
                <label for="username" class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                <input
                    type="text"
                    id="username"
                    wire:model="username"
                    autocomplete="username"
                    autofocus
                    class="w-full min-h-[48px] rounded-lg border-gray-300 focus:border-brand focus:ring-brand px-4"
                >
                @error('username')
                    <p class="mt-1 text-sm text-status-full">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                <input
                    type="password"
                    id="password"
                    wire:model="password"
                    autocomplete="current-password"
                    class="w-full min-h-[48px] rounded-lg border-gray-300 focus:border-brand focus:ring-brand px-4"
                >
                @error('password')
                    <p class="mt-1 text-sm text-status-full">{{ $message }}</p>
                @enderror
            </div>

            {{-- Deliberately no "Forgot password?" link — password reset is
                 admin-only and offline per the Architecture Specification. --}}

            <button
                type="submit"
                wire:loading.attr="disabled"
                wire:target="login"
                class="w-full min-h-[48px] rounded-lg bg-brand text-white font-medium disabled:opacity-60"
            >
                <span wire:loading.remove wire:target="login">Log In</span>
                <span wire:loading wire:target="login">Logging in…</span>
            </button>
        </form>
    </div>
</div>
