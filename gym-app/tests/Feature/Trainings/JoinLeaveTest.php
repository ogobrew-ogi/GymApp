<?php

declare(strict_types=1);

namespace Tests\Feature\Trainings;

use App\Livewire\Trainings\Show;
use App\Livewire\Trainings\TrainingList;
use App\Models\Training;
use App\Models\User;
use App\Support\LocalTime;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

final class JoinLeaveTest extends TestCase
{
    use RefreshDatabase;

    private function scheduledTraining(User $owner, int $maxParticipants = 5): Training
    {
        $date = CarbonImmutable::now()->addDay()->toDateString();

        return Training::factory()->create([
            'owner_id' => $owner->id,
            'date' => $date,
            'start_time' => LocalTime::toUtc($date, '18:00'),
            'end_time' => LocalTime::toUtc($date, '19:00'),
            'max_participants' => $maxParticipants,
        ]);
    }

    public function test_a_member_can_join_a_training_from_the_home_list(): void
    {
        $owner = User::factory()->create();
        $training = $this->scheduledTraining($owner);
        $joiner = User::factory()->create();

        Livewire::actingAs($joiner)
            ->test(TrainingList::class)
            ->call('joinTraining', $training->id);

        $this->assertTrue(
            $training->trainingParticipants()->where('user_id', $joiner->id)->exists()
        );
    }

    public function test_joining_a_full_training_is_rejected(): void
    {
        $owner = User::factory()->create();
        $training = $this->scheduledTraining($owner, maxParticipants: 1);
        $training->trainingParticipants()->create(['user_id' => $owner->id, 'joined_at' => now()]);

        $latecomer = User::factory()->create();

        Livewire::actingAs($latecomer)
            ->test(TrainingList::class)
            ->call('joinTraining', $training->id)
            ->assertHasErrors('training');

        $this->assertFalse(
            $training->trainingParticipants()->where('user_id', $latecomer->id)->exists()
        );
    }

    public function test_a_participant_can_leave(): void
    {
        $owner = User::factory()->create();
        $training = $this->scheduledTraining($owner);
        $participant = User::factory()->create();
        $training->trainingParticipants()->create(['user_id' => $participant->id, 'joined_at' => now()]);

        Livewire::actingAs($participant)
            ->test(Show::class, ['training' => $training])
            ->call('leave');

        $this->assertFalse(
            $training->trainingParticipants()->where('user_id', $participant->id)->exists()
        );
    }

    public function test_the_owner_can_never_leave_their_own_training(): void
    {
        $owner = User::factory()->create();
        $training = $this->scheduledTraining($owner);
        $training->trainingParticipants()->create(['user_id' => $owner->id, 'joined_at' => now()]);

        $this->assertFalse($owner->can('leave', $training));
    }
}
