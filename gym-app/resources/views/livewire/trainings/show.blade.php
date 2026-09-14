@php
    $training = $this->training;
    $user = auth()->user();
    $participants = $training->trainingParticipants;
    $count = $participants->count();
    $isOwner = $training->owner_id === $user->id;
    $isParticipant = $participants->contains('user_id', $user->id);
    $isFull = $count >= $training->max_participants;
    $isScheduled = $training->status->value === 'scheduled';
    $localStart = $training->start_time->timezone('Europe/Sofia');
    $localEnd = $training->end_time->timezone('Europe/Sofia');
@endphp

<div class="max-w-md mx-auto px-4 pt-4 pb-8 space-y-4">
    <a href="{{ route('trainings.index') }}" wire:navigate class="text-sm text-gray-500">&larr; Back</a>

    @error('training')
        <div class="bg-red-50 text-status-full text-sm rounded-lg p-3">{{ $message }}</div>
    @enderror

    <div class="bg-white rounded-2xl shadow-sm p-5 space-y-4">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-2xl font-semibold">{{ $localStart->format('H:i') }}–{{ $localEnd->format('H:i') }}</p>
                <p class="text-sm text-gray-500">{{ $localStart->isoFormat('dddd, MMM D') }}</p>
            </div>

            @unless ($isScheduled)
                <span class="text-xs font-medium px-2 py-1 rounded-full bg-gray-100 text-status-cancelled">
                    {{ $training->status->label() }}
                </span>
            @endunless
        </div>

        <p class="text-sm text-gray-600">Organized by <strong>{{ $training->owner->name }}</strong></p>

        @if ($training->note)
            <p class="text-sm text-gray-600 bg-gray-50 rounded-lg p-3">{{ $training->note }}</p>
        @endif

        <div>
            <p class="text-sm font-medium text-gray-700 mb-2">
                Participants ({{ $count }} of {{ $training->max_participants }})
            </p>
            <ul class="space-y-1">
                @foreach ($participants as $participant)
                    <li class="text-sm text-gray-600">
                        {{ $participant->user->name }}
                        @if ($participant->user_id === $training->owner_id)
                            <span class="text-gray-400">· organizer</span>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>

        @if ($isScheduled)
            <div class="pt-2 space-y-2">
                {{-- Primary action: exactly one of Join / Leave / nothing --}}
                @if ($isParticipant && ! $isOwner)
                    <button
                        type="button"
                        wire:click="leave"
                        wire:loading.attr="disabled"
                        wire:target="leave"
                        class="w-full min-h-[48px] rounded-lg border border-gray-300 font-medium"
                    >
                        You're in ✓ · Leave
                    </button>
                @elseif (! $isOwner && ! $isFull)
                    <button
                        type="button"
                        wire:click="joinTraining"
                        wire:loading.attr="disabled"
                        wire:target="joinTraining"
                        class="w-full min-h-[48px] rounded-lg bg-brand text-white font-medium"
                    >
                        Join
                    </button>
                @elseif (! $isOwner && $isFull)
                    <span class="block text-center text-sm text-gray-400 py-2">This training is full</span>
                @endif

                {{-- Secondary/destructive owner actions --}}
                @if ($isOwner)
                    <div class="flex gap-2">
                        @can('update', $training)
                            <a
                                href="{{ route('trainings.edit', $training) }}"
                                wire:navigate
                                class="flex-1 text-center min-h-[44px] leading-[44px] rounded-lg border border-gray-300 text-sm font-medium"
                            >
                                Edit
                            </a>
                        @endcan

                        @can('cancel', $training)
                            <button
                                type="button"
                                wire:click="confirmCancel"
                                class="flex-1 min-h-[44px] rounded-lg border border-status-full text-status-full text-sm font-medium"
                            >
                                Cancel Training
                            </button>
                        @endcan

                        @can('delete', $training)
                            <button
                                type="button"
                                wire:click="confirmDelete"
                                class="flex-1 min-h-[44px] rounded-lg border border-status-full text-status-full text-sm font-medium"
                            >
                                Delete
                            </button>
                        @endcan
                    </div>
                @endif
            </div>
        @endif
    </div>

    {{-- Single lightweight confirmation step (UX/UI Spec §9.6) --}}
    @if ($confirmingCancel)
        <div class="bg-white rounded-2xl shadow-sm p-5 space-y-3 border border-status-full/30">
            <p class="text-sm text-gray-700">
                Cancelling will notify no one automatically, but the training will be marked
                Cancelled and stay visible to everyone who joined. This can't be undone.
            </p>
            <div class="flex gap-2">
                <button wire:click="$set('confirmingCancel', false)" class="flex-1 min-h-[44px] rounded-lg border border-gray-300 text-sm font-medium">
                    Go Back
                </button>
                <button wire:click="cancel" wire:loading.attr="disabled" class="flex-1 min-h-[44px] rounded-lg bg-status-full text-white text-sm font-medium">
                    Confirm Cancel
                </button>
            </div>
        </div>
    @endif

    @if ($confirmingDelete)
        <div class="bg-white rounded-2xl shadow-sm p-5 space-y-3 border border-status-full/30">
            <p class="text-sm text-gray-700">Delete this training? This can't be undone.</p>
            <div class="flex gap-2">
                <button wire:click="$set('confirmingDelete', false)" class="flex-1 min-h-[44px] rounded-lg border border-gray-300 text-sm font-medium">
                    Go Back
                </button>
                <button wire:click="delete" wire:loading.attr="disabled" class="flex-1 min-h-[44px] rounded-lg bg-status-full text-white text-sm font-medium">
                    Confirm Delete
                </button>
            </div>
        </div>
    @endif
</div>
