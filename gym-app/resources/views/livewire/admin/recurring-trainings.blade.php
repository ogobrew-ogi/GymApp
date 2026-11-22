<div class="max-w-md mx-auto px-4 pt-4 pb-8 space-y-4">
    <a href="{{ route('profile') }}" wire:navigate class="text-sm text-gray-500">&larr; Back</a>

    <div>
        <h1 class="text-xl font-semibold">Regular Trainings</h1>
        <p class="text-sm text-gray-500 mt-1">
            Reserve a weekly time. It appears on Home for the next 30 days and members
            join it while there are spots. Nobody else can book over it.
        </p>
    </div>

    @if ($lastResult !== null)
        <div class="bg-green-50 text-status-open text-sm rounded-lg p-3 space-y-1">
            <p>Saved. {{ $lastResult['created'] }} training(s) added.</p>
            @if (count($lastResult['skipped']) > 0)
                <p class="text-status-caution">
                    Skipped (already booked or gym closed):
                    {{ collect($lastResult['skipped'])->map(fn ($d) => \Carbon\CarbonImmutable::parse($d)->format('D M j'))->implode(', ') }}
                </p>
            @endif
        </div>
    @endif

    @if ($lastRemovedCount !== null)
        <div class="bg-gray-100 text-gray-700 text-sm rounded-lg p-3">
            Removed. {{ $lastRemovedCount }} upcoming training(s) deleted.
        </div>
    @endif

    <form wire:submit="save" class="bg-white rounded-2xl shadow-sm p-5 space-y-4">
        <div>
            <p class="block text-sm font-medium text-gray-700 mb-2">Days</p>
            <div class="grid grid-cols-7 gap-1.5">
                @foreach ($weekdayLabels as $value => $label)
                    <label class="cursor-pointer">
                        <input type="checkbox" wire:model="weekdays" value="{{ $value }}" class="peer sr-only">
                        <span class="flex items-center justify-center h-11 rounded-lg border border-gray-300 text-xs font-medium
                            peer-checked:bg-brand peer-checked:border-brand peer-checked:text-white">
                            {{ $label }}
                        </span>
                    </label>
                @endforeach
            </div>
            @error('weekdays') <p class="mt-1 text-sm text-status-full">{{ $message }}</p> @enderror
        </div>

        <div class="grid grid-cols-2 gap-3">
            <div>
                <label for="start_time" class="block text-sm font-medium text-gray-700 mb-1">Start</label>
                <input type="time" id="start_time" wire:model="start_time" class="w-full min-h-[48px] rounded-lg border-gray-300 px-4">
                @error('start_time') <p class="mt-1 text-sm text-status-full">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="end_time" class="block text-sm font-medium text-gray-700 mb-1">End</label>
                <input type="time" id="end_time" wire:model="end_time" class="w-full min-h-[48px] rounded-lg border-gray-300 px-4">
                @error('end_time') <p class="mt-1 text-sm text-status-full">{{ $message }}</p> @enderror
            </div>
        </div>

        <div>
            <label for="max_participants" class="block text-sm font-medium text-gray-700 mb-1">Spots</label>
            <input type="number" id="max_participants" wire:model="max_participants" min="1" max="100" class="w-full min-h-[48px] rounded-lg border-gray-300 px-4">
            @error('max_participants') <p class="mt-1 text-sm text-status-full">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="note" class="block text-sm font-medium text-gray-700 mb-1">Note (optional)</label>
            <textarea id="note" wire:model="note" maxlength="200" rows="2" class="w-full rounded-lg border-gray-300 px-4 py-2"></textarea>
            @error('note') <p class="mt-1 text-sm text-status-full">{{ $message }}</p> @enderror
        </div>

        <button type="submit" wire:loading.attr="disabled" class="w-full min-h-[48px] rounded-lg bg-brand text-white font-medium">
            Add Regular Training
        </button>
    </form>

    <div class="space-y-3">
        @foreach ($this->slots as $slot)
            <div class="bg-white rounded-2xl shadow-sm p-4 space-y-2" wire:key="slot-{{ $slot->id }}">
                <div class="flex items-center justify-between">
                    <p class="font-medium">{{ $slot->weekdaysLabel() }}</p>
                    <p class="font-semibold">{{ $slot->start_time }}–{{ $slot->end_time }}</p>
                </div>
                <p class="text-sm text-gray-500">
                    {{ $slot->max_participants }} spots · {{ $slot->upcoming_count }} upcoming
                </p>
                @if ($slot->note)
                    <p class="text-sm text-gray-600">{{ $slot->note }}</p>
                @endif
                <p class="text-xs text-gray-400">Added by {{ $slot->creator->name }}</p>

                @if ($confirmingDeleteId === $slot->id)
                    <div class="space-y-2 pt-1">
                        <p class="text-sm text-gray-700">
                            This also deletes its {{ $slot->upcoming_count }} upcoming training(s), including anyone who joined.
                        </p>
                        <div class="flex gap-2">
                            <button wire:click="cancelDelete" class="flex-1 min-h-[40px] rounded-lg border border-gray-300 text-sm font-medium">
                                Go Back
                            </button>
                            <button wire:click="delete" class="flex-1 min-h-[40px] rounded-lg bg-status-full text-white text-sm font-medium">
                                Confirm Remove
                            </button>
                        </div>
                    </div>
                @else
                    <button wire:click="confirmDelete({{ $slot->id }})" class="text-sm text-status-full font-medium">
                        Remove
                    </button>
                @endif
            </div>
        @endforeach
    </div>
</div>
