<?php

declare(strict_types=1);

namespace Tests\Feature\Trainings;

use App\Enums\TrainingStatus;
use App\Livewire\Trainings\TrainingList;
use App\Models\Announcement;
use App\Models\Training;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

final class PurgeAndHomeTest extends TestCase
{
    use RefreshDatabase;

    public function test_purge_deletes_every_ended_training_and_keeps_upcoming_ones(): void
    {
        $ended = Training::factory()->past()->create();
        $endedCancelled = Training::factory()->past()->create(['status' => TrainingStatus::Cancelled]);
        $upcoming = Training::factory()->create();

        $ended->trainingParticipants()->create(['user_id' => $ended->owner_id, 'joined_at' => now()]);

        $this->artisan('trainings:purge-past')->assertSuccessful();

        $this->assertModelMissing($ended);
        $this->assertModelMissing($endedCancelled);
        $this->assertModelExists($upcoming);
        $this->assertDatabaseMissing('training_participants', ['training_id' => $ended->id]);
    }

    public function test_home_hides_ended_trainings_even_before_the_purge_runs(): void
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $ended = Training::factory()->past()->create();
        $upcoming = Training::factory()->create();

        $ids = Livewire::actingAs($user)->test(TrainingList::class)->get('trainings')->pluck('id')->all();

        $this->assertNotContains($ended->id, $ids);
        $this->assertContains($upcoming->id, $ids);
    }

    public function test_home_keeps_showing_an_upcoming_cancelled_training(): void
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $cancelled = Training::factory()->create(['status' => TrainingStatus::Cancelled]);

        Livewire::actingAs($user)
            ->test(TrainingList::class)
            ->assertSee('Cancelled');

        $this->assertContains(
            $cancelled->id,
            Livewire::actingAs($user)->test(TrainingList::class)->get('trainings')->pluck('id')->all(),
        );
    }

    public function test_day_strip_flags_days_that_have_trainings_and_toggles_selection(): void
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $tomorrow = CarbonImmutable::now('Europe/Sofia')->addDay()->setTime(18, 0);

        Training::factory()->create([
            'date' => $tomorrow->toDateString(),
            'start_time' => $tomorrow->utc(),
            'end_time' => $tomorrow->addHour()->utc(),
        ]);

        $component = Livewire::actingAs($user)->test(TrainingList::class);
        $days = collect($component->get('days'))->keyBy('date');

        $this->assertCount(31, $days);
        $this->assertTrue($days[$tomorrow->toDateString()]['hasTrainings']);
        $this->assertFalse($days[$tomorrow->addDays(5)->toDateString()]['hasTrainings']);

        $component->call('selectDate', $tomorrow->toDateString())
            ->assertSet('selectedDate', $tomorrow->toDateString())
            ->call('selectDate', $tomorrow->toDateString())
            ->assertSet('selectedDate', '');
    }

    public function test_history_page_is_gone(): void
    {
        $user = User::factory()->create(['must_change_password' => false]);

        $this->actingAs($user)->get('/history')->assertNotFound();
    }

    public function test_every_app_page_shows_the_logo(): void
    {
        $user = User::factory()->create(['must_change_password' => false]);

        $this->actingAs($user)->get(route('trainings.index'))->assertSee('images/logo.jpg', false);
        $this->actingAs($user)->get(route('profile'))->assertSee('images/logo.jpg', false);
    }

    public function test_home_shows_active_announcements_only(): void
    {
        $user = User::factory()->create(['must_change_password' => false]);

        Announcement::query()->create(['message' => 'Gym party on Friday', 'created_by' => $user->id]);
        Announcement::query()->create([
            'message' => 'Old news',
            'expires_at' => now()->subMinute(),
            'created_by' => $user->id,
        ]);

        Livewire::actingAs($user)
            ->test(TrainingList::class)
            ->assertSee('Gym party on Friday')
            ->assertDontSee('Old news');
    }
}
