<div class="max-w-md mx-auto px-4 pt-4 pb-8 space-y-4">
    <a href="{{ route('profile') }}" wire:navigate class="text-sm text-gray-500">&larr; Back</a>

    <div>
        <h1 class="text-xl font-semibold">Announcements</h1>
        <p class="text-sm text-gray-500 mt-1">Shown to everyone at the top of Home.</p>
    </div>

    @if ($saved)
        <div class="bg-green-50 text-status-open text-sm rounded-lg p-3">Announcement posted.</div>
    @endif

    <form wire:submit="save" class="bg-white rounded-2xl shadow-sm p-5 space-y-4">
        <div>
            <label for="body" class="block text-sm font-medium text-gray-700 mb-1">Message</label>
            <textarea id="body" wire:model="body" maxlength="500" rows="4" class="w-full rounded-lg border-gray-300 px-4 py-2"></textarea>
            @error('body') <p class="mt-1 text-sm text-status-full">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="expires_on" class="block text-sm font-medium text-gray-700 mb-1">Show until (optional)</label>
            <input type="date" id="expires_on" wire:model="expires_on" min="{{ now('Europe/Sofia')->toDateString() }}" class="w-full min-h-[48px] rounded-lg border-gray-300 px-4">
            <p class="mt-1 text-xs text-gray-500">Leave empty to keep it until you remove it.</p>
            @error('expires_on') <p class="mt-1 text-sm text-status-full">{{ $message }}</p> @enderror
        </div>

        <button type="submit" wire:loading.attr="disabled" class="w-full min-h-[48px] rounded-lg bg-brand text-white font-medium">
            Post Announcement
        </button>
    </form>

    <div class="space-y-3">
        @foreach ($this->announcements as $announcement)
            @php $isActive = $announcement->expires_at === null || $announcement->expires_at->isFuture(); @endphp
            <div class="bg-white rounded-2xl shadow-sm p-4 space-y-2" wire:key="announcement-{{ $announcement->id }}">
                <div class="flex items-center justify-between gap-2">
                    <p class="text-xs text-gray-400">
                        {{ $announcement->creator->name }} · {{ $announcement->created_at->timezone('Europe/Sofia')->format('M j, H:i') }}
                    </p>
                    @if ($isActive)
                        <span class="text-xs font-medium px-2 py-1 rounded-full bg-green-50 text-status-open">
                            {{ $announcement->expires_at ? 'Until '.$announcement->expires_at->timezone('Europe/Sofia')->format('M j') : 'Active' }}
                        </span>
                    @else
                        <span class="text-xs font-medium px-2 py-1 rounded-full bg-gray-100 text-status-cancelled">Expired</span>
                    @endif
                </div>
                <p class="text-sm text-gray-800 whitespace-pre-line break-words">{{ $announcement->message }}</p>

                @if ($confirmingDeleteId === $announcement->id)
                    <div class="flex gap-2 pt-1">
                        <button wire:click="cancelDelete" class="flex-1 min-h-[40px] rounded-lg border border-gray-300 text-sm font-medium">
                            Go Back
                        </button>
                        <button wire:click="delete" class="flex-1 min-h-[40px] rounded-lg bg-status-full text-white text-sm font-medium">
                            Confirm Remove
                        </button>
                    </div>
                @else
                    <button wire:click="confirmDelete({{ $announcement->id }})" class="text-sm text-status-full font-medium">
                        Remove
                    </button>
                @endif
            </div>
        @endforeach
    </div>
</div>
