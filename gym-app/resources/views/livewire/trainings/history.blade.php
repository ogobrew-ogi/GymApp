<div class="max-w-md mx-auto px-4 pt-4 pb-8 space-y-6">
    <h1 class="text-xl font-semibold">History</h1>

    @php
        $grouped = $this->trainings->groupBy(
            fn ($training) => $training->start_time->timezone('Europe/Sofia')->toDateString()
        );
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
        <div class="text-center py-16">
            <p class="text-gray-500">You haven't completed any trainings yet.</p>
        </div>
    @endforelse

    @if ($this->hasMore)
        <button
            wire:click="loadMore"
            wire:loading.attr="disabled"
            class="w-full min-h-[48px] rounded-lg border border-gray-300 text-sm font-medium disabled:opacity-60"
        >
            <span wire:loading.remove wire:target="loadMore">Load more</span>
            <span wire:loading wire:target="loadMore">Loading…</span>
        </button>
    @endif
</div>
