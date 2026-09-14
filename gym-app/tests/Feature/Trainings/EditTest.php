<?php

declare(strict_types=1);

namespace Tests\Feature\Trainings;

use App\Livewire\Trainings\Edit;
use App\Models\Training;
use App\Models\User;
use App\Support\LocalTime;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

final class EditTest extends TestCase
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

    public function test_time_is_editable_while_the_owner_is_the_sole_participant(): void
    {
        $owner = User::factory()->create();
        $training = $this->scheduledTraining($owner);
        $training->trainingParticipants()->create(['user_id' => $owner->id, 'joined_at' => now()]);

        Livewire::actingAs($owner)
            ->test(Edit::class, ['training' => $training])
            ->assertSet('timeLocked', false);
    }

    public function test_time_is_locked_once_someone_else_has_joined(): void
    {
        $owner = User::factory()->create();
        $training = $this->scheduledTraining($owner);
        $training->trainingParticipants()->create(['user_id' => $owner->id, 'joined_at' => now()]);
        $training->trainingParticipants()->create(['user_id' => User::factory()->create()->id, 'joined_at' => now()]);

        Livewire::actingAs($owner)
            ->test(Edit::class, ['training' => $training])
            ->assertSet('timeLocked', true);
    }

    public function test_note_and_capacity_remain_editable_even_when_time_is_locked(): void
    {
        $owner = User::factory()->create();
        $training = $this->scheduledTraining($owner);
        $training->trainingParticipants()->create(['user_id' => $owner->id, 'joined_at' => now()]);
        $training->trainingParticipants()->create(['user_id' => User::factory()->create()->id, 'joined_at' => now()]);

        $originalStart = $training->start_time;

        Livewire::actingAs($owner)
            ->test(Edit::class, ['training' => $training])
            ->set('note', 'Updated note')
            ->set('max_participants', 9)
            ->call('save')
            ->assertRedirect(route('trainings.show', $training));

        $training->refresh();
        $this->assertSame('Updated note', $training->note);
        $this->assertSame(9, $training->max_participants);
        $this->assertTrue($originalStart->equalTo($training->start_time), 'start_time must not change while locked');
    }

    public function test_a_non_owner_cannot_edit(): void
    {
        $owner = User::factory()->create();
        $training = $this->scheduledTraining($owner);
        $stranger = User::factory()->create();

        $this->assertFalse($stranger->can('update', $training));
    }
}
