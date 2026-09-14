<?php

declare(strict_types=1);

namespace Tests\Feature\Trainings;

use App\Enums\ClosureReason;
use App\Livewire\Trainings\Create;
use App\Models\GymClosure;
use App\Models\Training;
use App\Models\User;
use App\Support\LocalTime;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

final class CreateTrainingTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_a_training_auto_joins_the_owner_as_first_participant(): void
    {
        $owner = User::factory()->create();
        $date = CarbonImmutable::now()->addDay()->toDateString();

        Livewire::actingAs($owner)
            ->test(Create::class)
            ->set('date', $date)
            ->set('start_time', '18:00')
            ->set('end_time', '19:00')
            ->set('max_participants', 5)
            ->call('save');

        $training = Training::sole();
        $this->assertSame($owner->id, $training->owner_id);
        $this->assertSame(1, $training->trainingParticipants()->count());
        $this->assertSame($owner->id, $training->trainingParticipants()->first()->user_id);
    }

    public function test_an_overlapping_window_shows_the_join_instead_prompt_rather_than_creating_a_duplicate(): void
    {
        $existingOwner = User::factory()->create();
        $date = CarbonImmutable::now()->addDay()->toDateString();

        $existing = Training::factory()->create([
            'owner_id' => $existingOwner->id,
            'date' => $date,
            'start_time' => LocalTime::toUtc($date, '18:00'),
            'end_time' => LocalTime::toUtc($date, '19:00'),
        ]);

        $second = User::factory()->create();

        Livewire::actingAs($second)
            ->test(Create::class)
            ->set('date', $date)
            ->set('start_time', '18:30')
            ->set('end_time', '19:30')
            ->set('max_participants', 5)
            ->call('save')
            ->assertSet('conflictingTrainingId', $existing->id);

        $this->assertSame(1, Training::count(), 'no duplicate training should have been created');
    }

    public function test_join_instead_actually_joins_the_conflicting_training(): void
    {
        $existingOwner = User::factory()->create();
        $date = CarbonImmutable::now()->addDay()->toDateString();

        $existing = Training::factory()->create([
            'owner_id' => $existingOwner->id,
            'date' => $date,
            'start_time' => LocalTime::toUtc($date, '18:00'),
            'end_time' => LocalTime::toUtc($date, '19:00'),
        ]);

        $second = User::factory()->create();

        Livewire::actingAs($second)
            ->test(Create::class)
            ->set('date', $date)
            ->set('start_time', '18:30')
            ->set('end_time', '19:30')
            ->set('max_participants', 5)
            ->call('save')
            ->call('joinConflictingInstead')
            ->assertRedirect(route('trainings.show', $existing));

        $this->assertTrue(
            $existing->trainingParticipants()->where('user_id', $second->id)->exists()
        );
    }

    public function test_a_training_cannot_be_created_while_the_gym_is_closed(): void
    {
        $owner = User::factory()->create();
        $admin = User::factory()->admin()->create();
        $date = CarbonImmutable::now()->addDay()->toDateString();

        GymClosure::factory()->create([
            'starts_at' => LocalTime::toUtc($date, '00:00'),
            'ends_at' => LocalTime::toUtc($date, '23:59'),
            'reason' => ClosureReason::Maintenance,
            'created_by' => $admin->id,
        ]);

        Livewire::actingAs($owner)
            ->test(Create::class)
            ->set('date', $date)
            ->set('start_time', '18:00')
            ->set('end_time', '19:00')
            ->set('max_participants', 5)
            ->call('save')
            ->assertHasErrors('date');

        $this->assertSame(0, Training::count());
    }

    public function test_the_form_pre_fills_the_date_from_a_query_parameter(): void
    {
        // Real HTTP request (not Livewire::test()) since the query
        // string only flows through the actual route — this is what the
        // Home screen's "+ Create Training" link produces when a date
        // filter is active. The property's initial value lives in the
        // wire:snapshot JSON embedded in the HTML (Livewire hydrates
        // form fields client-side, not via a static `value=""`
        // attribute), so that's what's asserted against.
        $user = User::factory()->create(['must_change_password' => false]);
        $date = CarbonImmutable::now()->addDays(5)->toDateString();

        $this->actingAs($user)
            ->get("/trainings/create?date={$date}")
            ->assertSee('&quot;date&quot;:&quot;'.$date.'&quot;', false);
    }

    public function test_a_malformed_date_query_parameter_falls_back_to_today(): void
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $today = now('Europe/Sofia')->toDateString();

        $this->actingAs($user)
            ->get('/trainings/create?date=not-a-date')
            ->assertSee('&quot;date&quot;:&quot;'.$today.'&quot;', false);
    }
}
