<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Models\RecurringTraining;
use App\Services\RecurringTrainingService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Admin screen for weekly reserved slots, e.g. "Mon + Wed, 10:00–11:00".
 * Members can't book over a slot; they join its trainings instead.
 */
#[Layout('components.layouts.app')]
final class RecurringTrainings extends Component
{
    /** @var list<string> */
    public array $weekdays = [];

    public string $start_time = '10:00';

    public string $end_time = '11:00';

    public int $max_participants = 10;

    public string $note = '';

    public ?int $confirmingDeleteId = null;

    /** @var array{created: int, skipped: list<string>}|null */
    public ?array $lastResult = null;

    public ?int $lastRemovedCount = null;

    public function mount(): void
    {
        abort_unless(auth()->user()->is_admin, 403);
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'weekdays' => ['required', 'array', 'min:1'],
            'weekdays.*' => ['integer', 'between:1,7'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'max_participants' => ['required', 'integer', 'min:1', 'max:100'],
            'note' => ['nullable', 'string', 'max:200'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'weekdays.required' => __('Pick at least one day.'),
            'end_time.after' => __('End time must be after start time.'),
        ];
    }

    public function save(RecurringTrainingService $service): void
    {
        abort_unless(auth()->user()->is_admin, 403);

        $this->lastRemovedCount = null;
        $this->validate();

        $minutes = (strtotime($this->end_time) - strtotime($this->start_time)) / 60;

        if ($minutes < 30 || $minutes > 240) {
            $this->addError('end_time', __('Trainings must be between 30 minutes and 4 hours long.'));

            return;
        }

        $days = array_map('intval', $this->weekdays);

        $clash = RecurringTraining::query()->get()->first(
            fn (RecurringTraining $existing): bool => array_intersect($days, array_map('intval', $existing->weekdays)) !== []
                && $this->start_time < $existing->end_time
                && $this->end_time > $existing->start_time,
        );

        if ($clash instanceof RecurringTraining) {
            $this->addError('start_time', __('Overlaps another regular training (:days, :start–:end).', [
                'days' => $clash->weekdaysLabel(),
                'start' => $clash->start_time,
                'end' => $clash->end_time,
            ]));

            return;
        }

        $rule = RecurringTraining::query()->create([
            'weekdays' => array_values(array_map('intval', $this->weekdays)),
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'max_participants' => $this->max_participants,
            'note' => $this->note !== '' ? $this->note : null,
            'created_by' => auth()->id(),
        ]);

        $this->lastResult = $service->generate($rule);

        $this->reset(['weekdays', 'note']);
        unset($this->slots);
    }

    /**
     * @return Collection<int, RecurringTraining>
     */
    #[Computed]
    public function slots(): Collection
    {
        return RecurringTraining::query()
            ->with('creator')
            ->withCount(['trainings as upcoming_count' => fn ($q) => $q->where('end_time', '>', now())])
            ->orderBy('start_time')
            ->get();
    }

    public function confirmDelete(int $id): void
    {
        $this->confirmingDeleteId = $id;
    }

    public function cancelDelete(): void
    {
        $this->confirmingDeleteId = null;
    }

    public function delete(RecurringTrainingService $service): void
    {
        abort_unless(auth()->user()->is_admin, 403);

        if (! $this->confirmingDeleteId) {
            return;
        }

        $rule = RecurringTraining::query()->findOrFail($this->confirmingDeleteId);
        $this->lastRemovedCount = $service->delete($rule);
        $this->lastResult = null;
        $this->confirmingDeleteId = null;
        unset($this->slots);
    }

    public function render(): View
    {
        return view('livewire.admin.recurring-trainings', [
            'weekdayLabels' => RecurringTraining::WEEKDAY_LABELS,
        ]);
    }
}
