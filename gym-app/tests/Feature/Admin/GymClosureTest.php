<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Enums\TrainingStatus;
use App\Livewire\Admin\GymClosures;
use App\Models\GymClosure;
use App\Models\Training;
use App\Models\User;
use App\Support\LocalTime;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

final class GymClosureTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_non_admin_cannot_view_the_screen(): void
    {
        $member = User::factory()->create(['must_change_password' => false]);

        $this->actingAs($member)
            ->get('/admin/closures')
            ->assertForbidden();
    }

    public function test_creating_a_closure_cancels_every_overlapping_scheduled_training(): void
    {
        $admin = User::factory()->admin()->create();
        $owner = User::factory()->create();
        $date = CarbonImmutable::now()->addDay()->toDateString();

        $overlapping = Training::factory()->create([
            'owner_id' => $owner->id,
            'date' => $date,
            'start_time' => LocalTime::toUtc($date, '18:00'),
            'end_time' => LocalTime::toUtc($date, '19:00'),
        ]);

        $unaffected = Training::factory()->create([
            'owner_id' => $owner->id,
            'date' => CarbonImmutable::now()->addDays(10)->toDateString(),
            'start_time' => LocalTime::toUtc(CarbonImmutable::now()->addDays(10)->toDateString(), '18:00'),
            'end_time' => LocalTime::toUtc(CarbonImmutable::now()->addDays(10)->toDateString(), '19:00'),
        ]);

        Livewire::actingAs($admin)
            ->test(GymClosures::class)
            ->set('starts_at_date', $date)
            ->set('starts_at_time', '00:00')
            ->set('ends_at_date', $date)
            ->set('ends_at_time', '23:59')
            ->set('reason', 'maintenance')
            ->call('prepareConfirmation')
            ->assertSet('previewAffectedCount', 1)
            ->call('createClosure')
            ->assertSet('lastCancelledCount', 1);

        $this->assertSame(TrainingStatus::Cancelled, $overlapping->fresh()->status);
        $this->assertSame(TrainingStatus::Scheduled, $unaffected->fresh()->status);
        $this->assertSame(1, GymClosure::count());
    }

    public function test_deleting_a_closure_does_not_reinstate_the_trainings_it_cancelled(): void
    {
        $admin = User::factory()->admin()->create();
        $owner = User::factory()->create();
        $date = CarbonImmutable::now()->addDay()->toDateString();

        $training = Training::factory()->create([
            'owner_id' => $owner->id,
            'date' => $date,
            'start_time' => LocalTime::toUtc($date, '18:00'),
            'end_time' => LocalTime::toUtc($date, '19:00'),
        ]);

        $closure = GymClosure::factory()->create([
            'starts_at' => LocalTime::toUtc($date, '00:00'),
            'ends_at' => LocalTime::toUtc($date, '23:59'),
            'created_by' => $admin->id,
        ]);
        $training->update(['status' => TrainingStatus::Cancelled]);

        Livewire::actingAs($admin)
            ->test(GymClosures::class)
            ->call('confirmDelete', $closure->id)
            ->call('deleteClosure');

        $this->assertNull(GymClosure::find($closure->id));
        $this->assertSame(TrainingStatus::Cancelled, $training->fresh()->status);
    }
}
