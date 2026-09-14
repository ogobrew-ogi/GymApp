<div class="w-full max-w-sm">
    <div class="bg-white rounded-2xl shadow-sm p-6">
        <h1 class="text-xl font-semibold text-center mb-2">Set a New Password</h1>
        <p class="text-sm text-gray-500 text-center mb-6">Please choose a new password to continue.</p>

        <form wire:submit="save" class="space-y-4">
            <div>
                <label for="current_password" class="block text-sm font-medium text-gray-700 mb-1">Current password</label>
                <input
                    type="password"
                    id="current_password"
                    wire:model="current_password"
                    autocomplete="current-password"
                    autofocus
                    class="w-full min-h-[48px] rounded-lg border-gray-300 focus:border-brand focus:ring-brand px-4"
                >
                @error('current_password')
                    <p class="mt-1 text-sm text-status-full">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">New password</label>
                <input
                    type="password"
                    id="password"
                    wire:model="password"
                    autocomplete="new-password"
                    class="w-full min-h-[48px] rounded-lg border-gray-300 focus:border-brand focus:ring-brand px-4"
                >
                @error('password')
                    <p class="mt-1 text-sm text-status-full">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">Confirm new password</label>
                <input
                    type="password"
                    id="password_confirmation"
                    wire:model="password_confirmation"
                    autocomplete="new-password"
                    class="w-full min-h-[48px] rounded-lg border-gray-300 focus:border-brand focus:ring-brand px-4"
                >
            </div>

            <button
                type="submit"
                wire:loading.attr="disabled"
                wire:target="save"
                class="w-full min-h-[48px] rounded-lg bg-brand text-white font-medium disabled:opacity-60"
            >
                <span wire:loading.remove wire:target="save">Save</span>
                <span wire:loading wire:target="save">Saving…</span>
            </button>
        </form>
    </div>
</div>
