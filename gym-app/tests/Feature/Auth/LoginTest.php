<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Livewire\Auth\Login;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

final class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_credentials_log_the_user_in(): void
    {
        $user = User::factory()->create(['username' => 'alice', 'must_change_password' => false]);

        Livewire::test(Login::class)
            ->set('username', 'alice')
            ->set('password', 'password')
            ->call('login')
            ->assertRedirect(route('trainings.index'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_wrong_password_is_rejected_with_a_generic_message(): void
    {
        User::factory()->create(['username' => 'alice']);

        Livewire::test(Login::class)
            ->set('username', 'alice')
            ->set('password', 'wrong-password')
            ->call('login')
            ->assertHasErrors('username');

        $this->assertGuest();
    }

    public function test_unknown_username_is_rejected_with_the_same_generic_message(): void
    {
        Livewire::test(Login::class)
            ->set('username', 'nobody')
            ->set('password', 'password')
            ->call('login')
            ->assertHasErrors('username');

        $this->assertGuest();
    }

    public function test_disabled_users_cannot_log_in(): void
    {
        User::factory()->create(['username' => 'alice', 'is_active' => false]);

        Livewire::test(Login::class)
            ->set('username', 'alice')
            ->set('password', 'password')
            ->call('login')
            ->assertHasErrors('username');

        $this->assertGuest();
    }

    public function test_a_user_forced_to_change_password_is_redirected_there_on_first_visit(): void
    {
        $user = User::factory()->create(['must_change_password' => true]);

        $this->actingAs($user)
            ->get(route('trainings.index'))
            ->assertRedirect(route('password.change'));
    }
}
