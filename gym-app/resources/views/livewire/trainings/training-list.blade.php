<div class="max-w-md mx-auto px-4 pt-4 pb-4 space-y-6">
    @error('training')
        <div class="bg-red-50 text-status-full text-sm rounded-lg p-3">{{ $message }}</div>
    @enderror

    <div class="flex items-center gap-2">
        <input
            type="date"
            wire:model.live="selectedDate"
            min="{{ $minSelectableDate }}"
            max="{{ $maxSelectableDate }}"
            aria-label="Jump to date"
            class="flex-1 min-h-[48px] rounded-lg border-gray-300 px-4"
        >
        @if ($selectedDate !== '')
            <button
                type="button"
                wire:click="clearDateFilter"
                class="min-h-[48px] px-4 rounded-lg border border-gray-300 text-sm font-medium"
            >
                Clear
            </button>
        @endif
    </div>

    @php
        $grouped = $this->trainings->groupBy(
            fn ($training) => $training->start_time->timezone('Europe/Sofia')->toDateString()
        );
        // Carries the active date filter into "Create Training" so
        // starting from a specific day opens the form pre-filled with
        // that day instead of always defaulting to today.
        $createUrl = $selectedDate !== ''
            ? route('trainings.create', ['date' => $selectedDate])
            : route('trainings.create');
    @endphp

    @forelse ($grouped as $dateKey => $trainingsForDate)
        <section class="space-y-3">
            <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide">
                {{ \App\Support\RelativeDateLabel::for($trainingsForDate->first()->start_time->timezone('Europe/Sofia')) }}
            </h2>

            <div class="space-y-3">
                @foreach ($trainingsForDate as $training)
                    <x-training-card :training="$training" wire:key="training-{{ $training->id }}" />
                @endforeach
            </div>
        </section>
    @empty
        <div class="text-center py-16 space-y-3">
            @if ($selectedDate !== '')
                <p class="text-gray-500 mb-1">No trainings on this date.</p>
                <a
                    href="{{ $createUrl }}"
                    wire:navigate
                    class="inline-block min-h-[48px] px-6 py-3 rounded-lg bg-brand text-white font-medium"
                >
                    + Create Training
                </a>
                <div>
                    <button type="button" wire:click="clearDateFilter" class="text-sm text-brand font-medium">
                        Show all upcoming trainings
                    </button>
                </div>
            @else
                <p class="text-gray-500 mb-4">No trainings scheduled yet.</p>
                <a
                    href="{{ $createUrl }}"
                    wire:navigate
                    class="inline-block min-h-[48px] px-6 py-3 rounded-lg bg-brand text-white font-medium"
                >
                    + Create Training
                </a>
            @endif
        </div>
    @endforelse

    @if ($grouped->isNotEmpty() || $selectedDate !== '')
        <a
            href="{{ $createUrl }}"
            wire:navigate
            class="fixed bottom-24 right-4 min-h-[56px] min-w-[56px] rounded-full bg-brand text-white flex items-center justify-center shadow-lg text-2xl"
            aria-label="Create Training"
        >
            +
        </a>
    @endif
</div>
