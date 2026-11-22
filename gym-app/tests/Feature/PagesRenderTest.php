<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\RecurringTraining;
use App\Models\Training;
use App\Models\User;
use App\Services\RecurringTrainingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Opens every screen with realistic data to catch template errors.
 */
final class PagesRenderTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_page_renders_for_admins_and_members(): void
    {
        $admin = User::factory()->admin()->create(['must_change_password' => false, 'phone' => '+359 888 000 111']);
        $member = User::factory()->create(['must_change_password' => false, 'phone' => '0888 222 333']);

        $rule = RecurringTraining::query()->create([
            'weekdays' => [1, 2, 3, 4, 5, 6, 7],
            'start_time' => '06:00',
            'end_time' => '07:00',
            'max_participants' => 5,
            'note' => 'Morning class',
            'created_by' => $admin->id,
        ]);
        app(RecurringTrainingService::class)->generate($rule);

        $regular = Training::query()->whereNotNull('recurring_training_id')->orderBy('start_time')->firstOrFail();
        $regular->trainingParticipants()->create(['user_id' => $member->id, 'joined_at' => now()]);

        $normal = Training::factory()->create(['owner_id' => $member->id]);
        $normal->trainingParticipants()->create(['user_id' => $member->id, 'joined_at' => now()]);

        Announcement::query()->create(['message' => 'Welcome!', 'created_by' => $admin->id]);

        foreach ([$admin, $member] as $user) {
            $this->actingAs($user);

            $this->get(route('trainings.index'))->assertOk()->assertSee('Welcome!')->assertSee('Regular training');
            $this->get(route('trainings.show', $regular))->assertOk()->assertSee('0888 222 333');
            $this->get(route('trainings.show', $normal))->assertOk();
            $this->get(route('trainings.create'))->assertOk();
            $this->get(route('profile'))->assertOk();
        }

        $this->actingAs($admin);
        $this->get(route('trainings.edit', $regular))->assertOk();
        $this->get(route('admin.recurring.index'))->assertOk()->assertSee('Morning class');
        $this->get(route('admin.announcements.index'))->assertOk()->assertSee('Welcome!');
        $this->get(route('admin.users.index'))->assertOk()->assertSee('0888 222 333');
        $this->get(route('admin.closures.index'))->assertOk();
        $this->get(route('trainings.show', $regular))->assertSee('Cancel Training');

        $this->actingAs($member);
        $this->get(route('trainings.edit', $normal))->assertOk();
        $this->get(route('trainings.show', $regular))->assertDontSee('Cancel Training');
    }
}
