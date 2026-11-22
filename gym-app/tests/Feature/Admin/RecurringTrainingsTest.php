<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Actions\Training\CreateTrainingAction;
use App\DTOs\TrainingData;
use App\Enums\TrainingStatus;
use App\Livewire\Admin\RecurringTrainings;
use App\Models\RecurringTraining;
use App\Models\Training;
use App\Models\User;
use App\Services\RecurringTrainingService;
use App\Support\LocalTime;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

final class RecurringTrainingsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        // Monday 16 Nov 2026, 08:00 in Sofia.
        $this->travelTo(CarbonImmutable::parse('2026-11-16 08:00', 'Europe/Sofia'));

        $this->admin = User::factory()->admin()->create(['must_change_password' => false]);
    }

    private function createSlot(array $weekdays = [1, 3], string $start = '10:00', string $end = '11:00'): void
    {
        Livewire::actingAs($this->admin)
            ->test(RecurringTrainings::class)
            ->set('weekdays', array_map('strval', $weekdays))
            ->set('start_time', $start)
            ->set('end_time', $end)
            ->set('max_participants', 8)
            ->call('save')
            ->assertHasNoErrors();
    }

    public function test_slot_generates_trainings_on_its_weekdays_with_every_spot_open(): void
    {
        $this->createSlot([1, 3]);

        $trainings = Training::query()->whereNotNull('recurring_training_id')->get();

        // Mon 16 Nov → Wed 16 Dec inclusive: 5 Mondays + 5 Wednesdays.
        $this->assertCount(10, $trainings);

        foreach ($trainings as $training) {
            $local = LocalTime::toLocal($training->start_time);
            $this->assertContains($local->isoWeekday(), [1, 3]);
            $this->assertSame('10:00', $local->format('H:i'));
            $this->assertSame(0, $training->trainingParticipants()->count());
            $this->assertSame(8, $training->max_participants);
        }
    }

    public function test_running_generation_again_creates_no_duplicates(): void
    {
        $this->createSlot([1]);
        $before = Training::query()->count();

        $this->artisan('trainings:generate-recurring')->assertSuccessful();

        $this->assertSame($before, Training::query()->count());
    }

    public function test_generation_extends_as_days_pass(): void
    {
        $this->createSlot([1]);
        $before = Training::query()->count();

        $this->travel(7)->days();
        $this->artisan('trainings:generate-recurring')->assertSuccessful();

        $this->assertSame($before + 1, Training::query()->count());
    }

    public function test_members_can_join_and_the_admin_owner_can_join_and_leave(): void
    {
        $this->createSlot([1]);
        $training = Training::query()->orderBy('start_time')->firstOrFail();
        $member = User::factory()->create();

        $this->assertTrue(Gate::forUser($member)->allows('join', $training));

        $this->assertTrue(Gate::forUser($this->admin)->allows('join', $training));
        $training->trainingParticipants()->create(['user_id' => $this->admin->id, 'joined_at' => now()]);
        $this->assertTrue(Gate::forUser($this->admin)->allows('leave', $training));
    }

    public function test_a_single_occurrence_is_cancelled_not_deleted_and_is_not_recreated(): void
    {
        $this->createSlot([1]);
        $training = Training::query()->orderBy('start_time')->firstOrFail();
        $otherAdmin = User::factory()->admin()->create();

        $this->assertFalse(Gate::forUser($this->admin)->allows('delete', $training));
        $this->assertTrue(Gate::forUser($otherAdmin)->allows('cancel', $training));
        $this->assertFalse(Gate::forUser(User::factory()->create())->allows('cancel', $training));

        $training->update(['status' => TrainingStatus::Cancelled]);
        $this->artisan('trainings:generate-recurring');

        $sameDay = Training::query()->whereDate('date', $training->date)->get();
        $this->assertCount(1, $sameDay);
        $this->assertSame(TrainingStatus::Cancelled, $sameDay->first()->status);
    }

    public function test_members_cannot_book_over_the_slot_even_beyond_generated_days(): void
    {
        $this->createSlot([1]);
        $member = User::factory()->create();

        // Monday 14 Dec — inside the window. And a Monday far outside the
        // generated range is still guarded by the service directly.
        $data = new TrainingData(
            startTime: LocalTime::toUtc('2026-12-14', '10:30'),
            endTime: LocalTime::toUtc('2026-12-14', '11:30'),
            maxParticipants: 4,
        );

        try {
            app(CreateTrainingAction::class)->execute($member, $data);
            $this->fail('Expected the reserved slot to block the booking.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('start_time', $e->errors());
        }

        $this->expectException(ValidationException::class);
        app(RecurringTrainingService::class)->assertSlotFree(new TrainingData(
            startTime: LocalTime::toUtc('2027-03-01', '09:30'),
            endTime: LocalTime::toUtc('2027-03-01', '10:15'),
            maxParticipants: 4,
        ));
    }

    public function test_booking_next_to_the_slot_is_still_allowed(): void
    {
        $this->createSlot([1]);
        $member = User::factory()->create();

        $training = app(CreateTrainingAction::class)->execute($member, new TrainingData(
            startTime: LocalTime::toUtc('2026-11-23', '11:00'),
            endTime: LocalTime::toUtc('2026-11-23', '12:00'),
            maxParticipants: 4,
        ));

        $this->assertModelExists($training);
    }

    public function test_days_already_booked_are_skipped_and_reported(): void
    {
        $member = User::factory()->create();
        $start = LocalTime::toUtc('2026-11-23', '10:00');
        Training::factory()->create([
            'owner_id' => $member->id,
            'date' => '2026-11-23',
            'start_time' => $start,
            'end_time' => $start->addHour(),
        ]);

        $component = Livewire::actingAs($this->admin)
            ->test(RecurringTrainings::class)
            ->set('weekdays', ['1'])
            ->call('save');

        $this->assertContains('2026-11-23', $component->get('lastResult')['skipped']);
        $this->assertSame(1, Training::query()->whereDate('date', '2026-11-23')->count());
    }

    public function test_overlapping_slots_are_rejected(): void
    {
        $this->createSlot([1, 3], '10:00', '11:00');

        Livewire::actingAs($this->admin)
            ->test(RecurringTrainings::class)
            ->set('weekdays', ['3', '5'])
            ->set('start_time', '10:30')
            ->set('end_time', '12:00')
            ->call('save')
            ->assertHasErrors('start_time');

        $this->assertSame(1, RecurringTraining::query()->count());
    }

    public function test_removing_a_slot_deletes_its_upcoming_trainings(): void
    {
        $this->createSlot([1]);
        $rule = RecurringTraining::query()->sole();
        $this->assertGreaterThan(0, $rule->trainings()->count());

        Livewire::actingAs($this->admin)
            ->test(RecurringTrainings::class)
            ->call('confirmDelete', $rule->id)
            ->call('delete');

        $this->assertModelMissing($rule);
        $this->assertSame(0, Training::query()->count());
    }
}
