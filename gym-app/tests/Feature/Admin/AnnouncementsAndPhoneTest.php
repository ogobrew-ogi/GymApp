<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Announcements;
use App\Livewire\Profile;
use App\Livewire\Trainings\Show;
use App\Models\Announcement;
use App\Models\Training;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

final class AnnouncementsAndPhoneTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_posts_and_removes_an_announcement(): void
    {
        $admin = User::factory()->admin()->create(['must_change_password' => false]);

        $component = Livewire::actingAs($admin)
            ->test(Announcements::class)
            ->set('body', '  No training on Monday  ')
            ->set('expires_on', now('Europe/Sofia')->addDays(3)->toDateString())
            ->call('save')
            ->assertHasNoErrors();

        $announcement = Announcement::query()->sole();
        $this->assertSame('No training on Monday', $announcement->message);
        $this->assertNotNull($announcement->expires_at);

        $component->call('confirmDelete', $announcement->id)->call('delete');

        $this->assertModelMissing($announcement);
    }

    public function test_members_cannot_open_the_admin_pages(): void
    {
        $member = User::factory()->create(['must_change_password' => false]);

        $this->actingAs($member)->get(route('admin.announcements.index'))->assertForbidden();
        $this->actingAs($member)->get(route('admin.recurring.index'))->assertForbidden();
    }

    public function test_user_saves_a_phone_number(): void
    {
        $user = User::factory()->create(['must_change_password' => false]);

        Livewire::actingAs($user)
            ->test(Profile::class)
            ->set('phone', ' +359 888 123 456 ')
            ->call('savePhone')
            ->assertHasNoErrors()
            ->assertSet('phoneSaved', true);

        $this->assertSame('+359 888 123 456', $user->fresh()->phone);
    }

    public function test_invalid_phone_is_rejected_and_empty_clears_it(): void
    {
        $user = User::factory()->create(['must_change_password' => false, 'phone' => '0888123456']);

        Livewire::actingAs($user)->test(Profile::class)
            ->set('phone', 'call me')
            ->call('savePhone')
            ->assertHasErrors('phone');

        $this->assertSame('0888123456', $user->fresh()->phone);

        Livewire::actingAs($user)->test(Profile::class)
            ->set('phone', '')
            ->call('savePhone')
            ->assertHasNoErrors();

        $this->assertNull($user->fresh()->phone);
    }

    public function test_participants_phone_numbers_are_visible_to_everyone(): void
    {
        $owner = User::factory()->create(['phone' => '+359 888 111 222']);
        $viewer = User::factory()->create(['must_change_password' => false]);

        $training = Training::factory()->create(['owner_id' => $owner->id]);
        $training->trainingParticipants()->create(['user_id' => $owner->id, 'joined_at' => now()]);

        Livewire::actingAs($viewer)
            ->test(Show::class, ['training' => $training])
            ->assertSee('+359 888 111 222')
            ->assertSee('tel:+359888111222', false);
    }
}
