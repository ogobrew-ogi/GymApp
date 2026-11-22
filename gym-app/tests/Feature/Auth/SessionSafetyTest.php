<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Actions\Auth\ChangePasswordAction;
use App\Livewire\Admin\UserManagement;
use App\Livewire\Auth\Login;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

final class SessionSafetyTest extends TestCase
{
    use RefreshDatabase;

    public function test_username_with_capitals_and_trailing_space_still_logs_in(): void
    {
        $user = User::factory()->create(['username' => 'erin', 'must_change_password' => false]);

        Livewire::test(Login::class)
            ->set('username', ' Erin ')
            ->set('password', 'password')
            ->call('login')
            ->assertRedirect(route('trainings.index'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_password_is_still_case_sensitive(): void
    {
        User::factory()->create(['username' => 'erin']);

        Livewire::test(Login::class)
            ->set('username', 'erin')
            ->set('password', 'PASSWORD')
            ->call('login')
            ->assertHasErrors('username');

        $this->assertGuest();
    }

    public function test_user_disabled_mid_session_is_signed_out_on_next_request(): void
    {
        $user = User::factory()->create(['must_change_password' => false]);

        $this->actingAs($user)->get(route('trainings.index'))->assertOk();

        $user->update(['is_active' => false]);

        $this->get(route('trainings.index'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('username');

        $this->assertGuest();
    }

    public function test_changing_password_removes_the_users_other_sessions(): void
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $other = User::factory()->create();

        DB::table('sessions')->insert([
            ['id' => 'phone', 'user_id' => $user->id, 'payload' => '', 'last_activity' => time()],
            ['id' => 'someone-else', 'user_id' => $other->id, 'payload' => '', 'last_activity' => time()],
        ]);

        app(ChangePasswordAction::class)->execute($user, 'password', 'new-secret-password');

        $this->assertDatabaseMissing('sessions', ['id' => 'phone']);
        $this->assertDatabaseHas('sessions', ['id' => 'someone-else']);
    }

    public function test_admin_cannot_create_username_differing_only_by_case(): void
    {
        $admin = User::factory()->admin()->create(['must_change_password' => false]);
        User::factory()->create(['username' => 'bob']);

        Livewire::actingAs($admin)
            ->test(UserManagement::class)
            ->set('name', 'Bob Two')
            ->set('username', 'Bob ')
            ->call('addUser')
            ->assertHasErrors('username');

        $this->assertSame(1, User::query()->whereRaw('lower(username) = ?', ['bob'])->count());
    }
}
