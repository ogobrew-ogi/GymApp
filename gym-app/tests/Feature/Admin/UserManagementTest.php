<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Livewire\Admin\UserManagement;
use App\Models\Training;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

final class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_non_admin_cannot_view_the_screen(): void
    {
        // Authorization here is enforced by route middleware
        // (`can:viewAny,...`), not inside the component itself, so this
        // has to go through the real route rather than Livewire::test(),
        // which would bypass it entirely.
        $member = User::factory()->create(['must_change_password' => false]);

        $this->actingAs($member)
            ->get('/admin/users')
            ->assertForbidden();
    }

    public function test_admin_creates_a_user_with_a_revealed_temporary_password_that_forces_a_change(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(UserManagement::class)
            ->set('name', 'New Member')
            ->set('username', 'newmember')
            ->call('addUser');

        $created = User::where('username', 'newmember')->sole();
        $this->assertFalse($created->is_admin);
        $this->assertTrue($created->must_change_password);
    }

    public function test_disabling_a_user_blocks_them_from_logging_in(): void
    {
        $admin = User::factory()->admin()->create();
        $member = User::factory()->create();

        Livewire::actingAs($admin)
            ->test(UserManagement::class)
            ->call('toggleActive', $member->id);

        $this->assertFalse($member->fresh()->is_active);
    }

    public function test_resetting_a_password_forces_a_change_on_next_login(): void
    {
        $admin = User::factory()->admin()->create();
        $member = User::factory()->create(['must_change_password' => false]);
        $originalHash = $member->password;

        Livewire::actingAs($admin)
            ->test(UserManagement::class)
            ->call('resetPassword', $member->id);

        $member->refresh();
        $this->assertNotSame($originalHash, $member->password);
        $this->assertTrue($member->must_change_password);
    }

    public function test_an_admin_cannot_delete_their_own_account(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(UserManagement::class)
            ->call('confirmDelete', $admin->id)
            ->call('deleteUser')
            ->assertHasErrors('user');

        $this->assertNotNull($admin->fresh());
    }

    public function test_deleting_a_user_cascades_their_owned_trainings_and_other_participations(): void
    {
        $admin = User::factory()->admin()->create();
        $target = User::factory()->create();

        $ownedTraining = Training::factory()->create(['owner_id' => $target->id]);
        $othersTraining = Training::factory()->create();
        $othersTraining->trainingParticipants()->create(['user_id' => $target->id, 'joined_at' => now()]);

        Livewire::actingAs($admin)
            ->test(UserManagement::class)
            ->call('confirmDelete', $target->id)
            ->call('deleteUser');

        $this->assertNull(User::find($target->id));
        $this->assertNull(Training::find($ownedTraining->id));
        $this->assertFalse(
            $othersTraining->trainingParticipants()->where('user_id', $target->id)->exists()
        );
    }
}
