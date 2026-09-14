<div class="max-w-md mx-auto px-4 pt-4 pb-8">
    <a href="{{ route('trainings.show', $training) }}" wire:navigate class="text-sm text-gray-500">&larr; Cancel</a>

    <h1 class="text-xl font-semibold mt-2 mb-4">Edit Training</h1>

    @if ($conflictingTraining)
        <div class="bg-white rounded-2xl shadow-sm p-5 space-y-4 border border-brand/30">
            <p class="text-sm text-gray-700">
                There's already a training at that time, organized by
                <strong>{{ $conflictingTraining->owner->name }}</strong>.
            </p>
            <p class="text-sm text-gray-500">
                {{ $conflictingTraining->start_time->timezone('Europe/Sofia')->format('H:i') }}–{{ $conflictingTraining->end_time->timezone('Europe/Sofia')->format('H:i') }}
            </p>
            <button wire:click="$set('conflictingTrainingId', null)" class="w-full min-h-[48px] rounded-lg border border-gray-300 text-sm font-medium">
                Pick a Different Time
            </button>
        </div>
    @else
        <form wire:submit="save" class="bg-white rounded-2xl shadow-sm p-5 space-y-4">
            @if ($timeLocked)
                <p class="text-xs text-gray-500 bg-gray-50 rounded-lg p-3">
                    The time can't be changed once someone else has joined.
                </p>
            @endif

            <div>
                <label for="date" class="block text-sm font-medium text-gray-700 mb-1">Date</label>
                <input
                    type="date"
                    id="date"
                    wire:model="date"
                    @disabled($timeLocked)
                    class="w-full min-h-[48px] rounded-lg border-gray-300 px-4 disabled:bg-gray-100 disabled:text-gray-400"
                >
                @error('date') <p class="mt-1 text-sm text-status-full">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label for="start_time" class="block text-sm font-medium text-gray-700 mb-1">Start</label>
                    <input
                        type="time"
                        id="start_time"
                        wire:model="start_time"
                        @disabled($timeLocked)
                        class="w-full min-h-[48px] rounded-lg border-gray-300 px-4 disabled:bg-gray-100 disabled:text-gray-400"
                    >
                    @error('start_time') <p class="mt-1 text-sm text-status-full">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="end_time" class="block text-sm font-medium text-gray-700 mb-1">End</label>
                    <input
                        type="time"
                        id="end_time"
                        wire:model="end_time"
                        @disabled($timeLocked)
                        class="w-full min-h-[48px] rounded-lg border-gray-300 px-4 disabled:bg-gray-100 disabled:text-gray-400"
                    >
                    @error('end_time') <p class="mt-1 text-sm text-status-full">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label for="max_participants" class="block text-sm font-medium text-gray-700 mb-1">Max participants</label>
                <input type="number" id="max_participants" wire:model="max_participants" min="1" class="w-full min-h-[48px] rounded-lg border-gray-300 px-4">
                @error('max_participants') <p class="mt-1 text-sm text-status-full">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="note" class="block text-sm font-medium text-gray-700 mb-1">Note (optional)</label>
                <textarea id="note" wire:model="note" maxlength="200" rows="2" class="w-full rounded-lg border-gray-300 px-4 py-2"></textarea>
                <p class="mt-1 text-xs text-gray-400">{{ strlen($note) }}/200</p>
                @error('note') <p class="mt-1 text-sm text-status-full">{{ $message }}</p> @enderror
            </div>

            <button
                type="submit"
                wire:loading.attr="disabled"
                wire:target="save"
                class="w-full min-h-[48px] rounded-lg bg-brand text-white font-medium disabled:opacity-60"
            >
                <span wire:loading.remove wire:target="save">Save Changes</span>
                <span wire:loading wire:target="save">Saving…</span>
            </button>
        </form>
    @endif
</div>
