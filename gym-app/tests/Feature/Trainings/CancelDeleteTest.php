<?php

declare(strict_types=1);

namespace Tests\Feature\Trainings;

use App\Enums\TrainingStatus;
use App\Livewire\Trainings\Show;
use App\Models\Training;
use App\Models\User;
use App\Support\LocalTime;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

final class CancelDeleteTest extends TestCase
{
    use RefreshDatabase;

    private function scheduledTraining(User $owner): Training
    {
        $date = CarbonImmutable::now()->addDay()->toDateString();

        return Training::factory()->create([
            'owner_id' => $owner->id,
            'date' => $date,
            'start_time' => LocalTime::toUtc($date, '18:00'),
            'end_time' => LocalTime::toUtc($date, '19:00'),
            'max_participants' => 5,
        ]);
    }

    public function test_a_solo_owner_can_delete_their_training(): void
    {
        $owner = User::factory()->create();
        $training = $this->scheduledTraining($owner);
        $training->trainingParticipants()->create(['user_id' => $owner->id, 'joined_at' => now()]);

        Livewire::actingAs($owner)
            ->test(Show::class, ['training' => $training])
            ->call('confirmDelete')
            ->call('delete')
            ->assertRedirect(route('trainings.index'));

        $this->assertNull(Training::find($training->id));
    }

    public function test_an_owner_cannot_delete_once_someone_else_has_joined(): void
    {
        $owner = User::factory()->create();
        $training = $this->scheduledTraining($owner);
        $training->trainingParticipants()->create(['user_id' => $owner->id, 'joined_at' => now()]);
        $training->trainingParticipants()->create(['user_id' => User::factory()->create()->id, 'joined_at' => now()]);

        $this->assertFalse($owner->can('delete', $training));
    }

    public function test_an_owner_can_cancel_once_someone_else_has_joined_and_it_stays_visible(): void
    {
        $owner = User::factory()->create();
        $training = $this->scheduledTraining($owner);
        $training->trainingParticipants()->create(['user_id' => $owner->id, 'joined_at' => now()]);
        $joiner = User::factory()->create();
        $training->trainingParticipants()->create(['user_id' => $joiner->id, 'joined_at' => now()]);

        Livewire::actingAs($owner)
            ->test(Show::class, ['training' => $training])
            ->call('confirmCancel')
            ->call('cancel');

        $training->refresh();
        $this->assertSame(TrainingStatus::Cancelled, $training->status);
        $this->assertSame(2, $training->trainingParticipants()->count(), 'participants stay attached to a cancelled training');
    }

    public function test_an_owner_cannot_cancel_a_training_only_they_are_in(): void
    {
        $owner = User::factory()->create();
        $training = $this->scheduledTraining($owner);
        $training->trainingParticipants()->create(['user_id' => $owner->id, 'joined_at' => now()]);

        $this->assertFalse($owner->can('cancel', $training));
    }
}
