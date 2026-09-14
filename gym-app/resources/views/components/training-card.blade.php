<div class="bg-white rounded-2xl shadow-sm p-4 flex flex-col gap-2" wire:key="training-{{ $training->id }}">
    <div class="flex items-start justify-between">
        <div>
            <p class="text-lg font-semibold">
                {{ $training->start_time->timezone('Europe/Sofia')->format('H:i') }}
                –
                {{ $training->end_time->timezone('Europe/Sofia')->format('H:i') }}
            </p>
            <p class="text-sm text-gray-500">Organized by {{ $training->owner->name }}</p>
        </div>

        @if ($training->status->value !== 'scheduled')
            <span class="text-xs font-medium px-2 py-1 rounded-full bg-gray-100 text-status-cancelled">
                {{ $training->status->label() }}
            </span>
        @elseif ($isFull)
            <span class="text-xs font-medium px-2 py-1 rounded-full bg-red-50 text-status-full">Full</span>
        @endif
    </div>

    <div class="flex items-center justify-between text-sm">
        <span class="text-gray-600">
            {{ $training->trainingParticipants->count() }} of {{ $training->max_participants }} going
        </span>

        @if ($training->status->value === 'scheduled' && ! $isFull)
            <span class="text-status-open font-medium">{{ $spotsRemaining }} spot{{ $spotsRemaining === 1 ? '' : 's' }} left</span>
        @endif
    </div>

    @if ($training->note)
        <p class="text-sm text-gray-500 truncate">{{ $training->note }}</p>
    @endif

    @if ($training->status->value === 'scheduled')
        <div class="mt-1">
            @if ($isJoined)
                @if ($canLeave)
                    <button
                        type="button"
                        wire:click="leave({{ $training->id }})"
                        wire:loading.attr="disabled"
                        wire:target="leave({{ $training->id }})"
                        class="w-full min-h-[44px] rounded-lg border border-gray-300 text-sm font-medium disabled:opacity-60"
                    >
                        <span wire:loading.remove wire:target="leave({{ $training->id }})">You're in ✓ · Leave</span>
                        <span wire:loading wire:target="leave({{ $training->id }})">Leaving…</span>
                    </button>
                @else
                    {{-- Owner: joined, but never sees Leave — a permanent
                         absence, not a disabled state (Architecture §5.4). --}}
                    <span class="block text-center text-sm text-gray-500 py-2">You're organizing this</span>
                @endif
            @elseif ($canJoin)
                <button
                    type="button"
                    wire:click="joinTraining({{ $training->id }})"
                    wire:loading.attr="disabled"
                    wire:target="joinTraining({{ $training->id }})"
                    class="w-full min-h-[44px] rounded-lg bg-brand text-white text-sm font-medium disabled:opacity-60"
                >
                    <span wire:loading.remove wire:target="joinTraining({{ $training->id }})">Join</span>
                    <span wire:loading wire:target="joinTraining({{ $training->id }})">Joining…</span>
                </button>
            @endif
        </div>
    @endif
</div>
