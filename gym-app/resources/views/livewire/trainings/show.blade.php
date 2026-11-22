@php
    $training = $this->training;
    $user = auth()->user();
    $participants = $training->trainingParticipants;
    $count = $participants->count();
    // On a regular training the admin owner holds no seat and joins or
    // leaves like anyone else, so only a normal owner is "organizing".
    $isOwner = $training->owner_id === $user->id && ! $training->isRecurring();
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

        @if ($training->isRecurring())
            <p class="text-sm text-gray-600">
                <span class="inline-block text-xs font-medium px-2 py-0.5 rounded-full bg-blue-50 text-brand mr-1">Regular</span>
                Set by <strong>{{ $training->owner->name }}</strong>
            </p>
        @else
            <p class="text-sm text-gray-600">Organized by <strong>{{ $training->owner->name }}</strong></p>
        @endif

        @if ($training->note)
            <p class="text-sm text-gray-600 bg-gray-50 rounded-lg p-3">{{ $training->note }}</p>
        @endif

        <div>
            <p class="text-sm font-medium text-gray-700 mb-2">
                Participants ({{ $count }} of {{ $training->max_participants }})
            </p>
            @if ($count === 0)
                <p class="text-sm text-gray-400">Nobody has joined yet.</p>
            @endif
            <ul class="divide-y divide-gray-100">
                @foreach ($participants as $participant)
                    <li class="flex items-center justify-between gap-3 py-2 text-sm">
                        <span class="text-gray-700 min-w-0 truncate">
                            {{ $participant->user->name }}
                            @if ($participant->user_id === $training->owner_id && ! $training->isRecurring())
                                <span class="text-gray-400">· organizer</span>
                            @endif
                        </span>
                        @if ($participant->user->phone)
                            <a
                                href="tel:{{ preg_replace('/[^0-9+]/', '', $participant->user->phone) }}"
                                class="shrink-0 inline-flex items-center gap-1.5 min-h-[36px] px-3 rounded-lg bg-gray-50 text-brand font-medium"
                            >
                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2" />
                                </svg>
                                {{ $participant->user->phone }}
                            </a>
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

                {{-- Secondary/destructive actions for whoever manages it --}}
                @if ($isOwner || $user->can('update', $training) || $user->can('cancel', $training))
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
                Cancelling will notify no one automatically. The training stays on the list
                marked Cancelled until its end time, then it is removed. This can't be undone.
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
