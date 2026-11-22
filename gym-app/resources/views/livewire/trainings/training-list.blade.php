<div class="max-w-md mx-auto px-4 pt-4 pb-4 space-y-5">
    {{-- Announcements from admins. Each can be hidden on this device;
         the choice is remembered in localStorage when it's available. --}}
    @foreach ($this->announcements as $announcement)
        <div
            wire:key="announcement-{{ $announcement->id }}"
            x-data="{
                key: 'announcement-hidden-{{ $announcement->id }}',
                hidden: false,
                init() { try { this.hidden = localStorage.getItem(this.key) === '1' } catch (e) {} },
                hide() { this.hidden = true; try { localStorage.setItem(this.key, '1') } catch (e) {} },
            }"
            x-show="! hidden"
            class="bg-amber-50 border border-amber-200 rounded-2xl p-4 flex gap-3"
        >
            <svg class="w-5 h-5 shrink-0 text-status-caution mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M11 5 6 9H3v6h3l5 4V5Zm4.5 3.5a5 5 0 0 1 0 7M18 6a8.5 8.5 0 0 1 0 12" />
            </svg>
            <div class="flex-1 min-w-0">
                <p class="text-sm text-gray-800 whitespace-pre-line break-words">{{ $announcement->message }}</p>
                <p class="text-xs text-gray-500 mt-1">{{ $announcement->creator->name }} · {{ $announcement->created_at->timezone('Europe/Sofia')->format('M j, H:i') }}</p>
            </div>
            <button type="button" x-on:click="hide()" class="shrink-0 -m-2 p-2 text-gray-400" aria-label="Hide announcement">
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" d="M6 6l12 12M18 6 6 18" />
                </svg>
            </button>
        </div>
    @endforeach

    @error('training')
        <div class="bg-red-50 text-status-full text-sm rounded-lg p-3">{{ $message }}</div>
    @enderror

    {{-- Day strip: replaces the bare date input, which showed nothing on
         Android until tapped. Swipe sideways; a dot marks busy days. --}}
    <div
        class="-mx-4"
        x-data
        x-init="$nextTick(() => $el.querySelector('[data-selected]')?.scrollIntoView({ inline: 'center', block: 'nearest' }))"
    >
        <div class="flex gap-2 overflow-x-auto px-4 pb-2 snap-x" style="scrollbar-width: none;">
            <button
                type="button"
                wire:click="clearDateFilter"
                @if ($selectedDate === '') data-selected @endif
                class="snap-start shrink-0 w-14 h-[72px] rounded-xl border text-sm font-semibold flex items-center justify-center
                    {{ $selectedDate === '' ? 'bg-brand border-brand text-white' : 'bg-white border-gray-200 text-gray-700' }}"
            >
                All
            </button>

            @foreach ($this->days as $day)
                @php $isSelected = $selectedDate === $day['date']; @endphp
                <button
                    type="button"
                    wire:key="day-{{ $day['date'] }}"
                    wire:click="selectDate('{{ $day['date'] }}')"
                    @if ($isSelected) data-selected @endif
                    aria-label="{{ $day['weekday'] }} {{ $day['month'] }} {{ $day['day'] }}{{ $day['hasTrainings'] ? ', has trainings' : '' }}"
                    aria-pressed="{{ $isSelected ? 'true' : 'false' }}"
                    class="snap-start shrink-0 w-14 h-[72px] rounded-xl border flex flex-col items-center justify-center gap-0.5
                        {{ $isSelected ? 'bg-brand border-brand text-white' : 'bg-white border-gray-200 text-gray-700' }}"
                >
                    <span class="text-[11px] font-medium {{ $isSelected ? 'text-white/90' : ($day['isToday'] ? 'text-brand' : 'text-gray-500') }}">{{ $day['weekday'] }}</span>
                    <span class="text-lg font-semibold leading-none">{{ $day['day'] }}</span>
                    <span class="h-1.5 w-1.5 rounded-full {{ $day['hasTrainings'] ? ($isSelected ? 'bg-white' : 'bg-brand') : 'bg-transparent' }}"></span>
                </button>
            @endforeach
        </div>
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
        <div class="text-center py-12 space-y-3">
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
            class="fixed bottom-24 right-4 h-[72px] w-[72px] rounded-full bg-brand text-white flex items-center justify-center shadow-xl ring-4 ring-white active:scale-95 transition"
            aria-label="Create Training"
        >
            <svg class="w-9 h-9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.75" aria-hidden="true">
                <path stroke-linecap="round" d="M12 5v14M5 12h14" />
            </svg>
        </a>
    @endif
</div>
