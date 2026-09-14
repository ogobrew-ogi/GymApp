<div class="max-w-md mx-auto px-4 pt-4 pb-8 space-y-4">
    <a href="{{ route('profile') }}" wire:navigate class="text-sm text-gray-500">&larr; Back</a>

    <h1 class="text-xl font-semibold">Gym Closures</h1>

    @if ($lastCancelledCount !== null)
        <div class="bg-green-50 text-status-open text-sm rounded-lg p-3">
            Closure added.
            @if ($lastCancelledCount > 0)
                {{ $lastCancelledCount }} training(s) were cancelled.
            @endif
        </div>
    @endif

    @if ($confirmingCreate)
        <div class="bg-white rounded-2xl shadow-sm p-5 space-y-3 border border-status-full/30">
            @if ($previewAffectedCount > 0)
                <p class="text-sm text-gray-700">
                    This will cancel <strong>{{ $previewAffectedCount }}</strong>
                    scheduled training{{ $previewAffectedCount === 1 ? '' : 's' }} — continue?
                </p>
            @else
                <p class="text-sm text-gray-700">
                    No scheduled trainings fall inside this window. Add the closure?
                </p>
            @endif
            <div class="flex gap-2">
                <button wire:click="cancelConfirmation" class="flex-1 min-h-[44px] rounded-lg border border-gray-300 text-sm font-medium">
                    Go Back
                </button>
                <button wire:click="createClosure" wire:loading.attr="disabled" class="flex-1 min-h-[44px] rounded-lg bg-status-full text-white text-sm font-medium">
                    Confirm
                </button>
            </div>
        </div>
    @else
        <form wire:submit="prepareConfirmation" class="bg-white rounded-2xl shadow-sm p-5 space-y-4">
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label for="starts_at_date" class="block text-sm font-medium text-gray-700 mb-1">Start date</label>
                    <input type="date" id="starts_at_date" wire:model="starts_at_date" class="w-full min-h-[48px] rounded-lg border-gray-300 px-4">
                    @error('starts_at_date') <p class="mt-1 text-sm text-status-full">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="starts_at_time" class="block text-sm font-medium text-gray-700 mb-1">Start time</label>
                    <input type="time" id="starts_at_time" wire:model="starts_at_time" class="w-full min-h-[48px] rounded-lg border-gray-300 px-4">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label for="ends_at_date" class="block text-sm font-medium text-gray-700 mb-1">End date</label>
                    <input type="date" id="ends_at_date" wire:model="ends_at_date" class="w-full min-h-[48px] rounded-lg border-gray-300 px-4">
                    @error('ends_at_date') <p class="mt-1 text-sm text-status-full">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="ends_at_time" class="block text-sm font-medium text-gray-700 mb-1">End time</label>
                    <input type="time" id="ends_at_time" wire:model="ends_at_time" class="w-full min-h-[48px] rounded-lg border-gray-300 px-4">
                </div>
            </div>

            <div>
                <label for="reason" class="block text-sm font-medium text-gray-700 mb-1">Reason</label>
                <select id="reason" wire:model="reason" class="w-full min-h-[48px] rounded-lg border-gray-300 px-4">
                    <option value="">Select a reason</option>
                    @foreach ($reasons as $r)
                        <option value="{{ $r->value }}">{{ $r->label() }}</option>
                    @endforeach
                </select>
                @error('reason') <p class="mt-1 text-sm text-status-full">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="note" class="block text-sm font-medium text-gray-700 mb-1">Note (optional)</label>
                <textarea id="note" wire:model="note" maxlength="255" rows="2" class="w-full rounded-lg border-gray-300 px-4 py-2"></textarea>
                @error('note') <p class="mt-1 text-sm text-status-full">{{ $message }}</p> @enderror
            </div>

            <button type="submit" class="w-full min-h-[48px] rounded-lg bg-brand text-white font-medium">
                Add Closure
            </button>
        </form>
    @endif

    <div class="space-y-3">
        @foreach ($this->closures as $closure)
            <div class="bg-white rounded-2xl shadow-sm p-4 space-y-2" wire:key="closure-{{ $closure->id }}">
                <div class="flex items-center justify-between">
                    <p class="font-medium">{{ $closure->reason->label() }}</p>
                    @if ($closure->ends_at->isFuture())
                        <span class="text-xs font-medium px-2 py-1 rounded-full bg-green-50 text-status-open">Upcoming</span>
                    @else
                        <span class="text-xs font-medium px-2 py-1 rounded-full bg-gray-100 text-status-cancelled">Past</span>
                    @endif
                </div>
                <p class="text-sm text-gray-500">
                    {{ $closure->starts_at->timezone('Europe/Sofia')->format('M j, H:i') }}
                    –
                    {{ $closure->ends_at->timezone('Europe/Sofia')->format('M j, H:i') }}
                </p>
                @if ($closure->note)
                    <p class="text-sm text-gray-600">{{ $closure->note }}</p>
                @endif
                <p class="text-xs text-gray-400">Added by {{ $closure->creator->name }}</p>

                @if ($confirmingDeleteId === $closure->id)
                    <div class="flex gap-2 pt-1">
                        <button wire:click="cancelDelete" class="flex-1 min-h-[40px] rounded-lg border border-gray-300 text-sm font-medium">
                            Go Back
                        </button>
                        <button wire:click="deleteClosure" class="flex-1 min-h-[40px] rounded-lg bg-status-full text-white text-sm font-medium">
                            Confirm Remove
                        </button>
                    </div>
                @else
                    <button wire:click="confirmDelete({{ $closure->id }})" class="text-sm text-status-full font-medium">
                        Remove
                    </button>
                @endif
            </div>
        @endforeach
    </div>
</div>
