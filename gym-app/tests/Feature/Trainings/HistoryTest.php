<?php

declare(strict_types=1);

namespace Tests\Feature\Trainings;

use App\Enums\TrainingStatus;
use App\Livewire\Trainings\History;
use App\Models\Training;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

final class HistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_completed_and_cancelled_trainings_appear(): void
    {
        $user = User::factory()->create();

        $scheduled = Training::factory()->create(['status' => TrainingStatus::Scheduled]);
        $completed = Training::factory()->completed()->create();
        $cancelled = Training::factory()->cancelled()->create();

        $shown = Livewire::actingAs($user)
            ->test(History::class)
            ->trainings;

        $ids = $shown->pluck('id')->all();

        $this->assertContains($completed->id, $ids);
        $this->assertContains($cancelled->id, $ids);
        $this->assertNotContains($scheduled->id, $ids);
    }

    public function test_most_recent_comes_first(): void
    {
        $user = User::factory()->create();

        $older = Training::factory()->completed()->create();
        $older->update(['start_time' => $older->start_time->subDays(10)]);
        $newer = Training::factory()->completed()->create();

        $shown = Livewire::actingAs($user)
            ->test(History::class)
            ->trainings;

        $this->assertSame($newer->id, $shown->first()->id);
    }

    public function test_empty_state_when_nothing_has_completed_or_been_cancelled(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(History::class)
            ->assertSee("You haven't completed any trainings yet.", false);
    }

    public function test_load_more_expands_the_visible_window(): void
    {
        $user = User::factory()->create();
        Training::factory()->completed()->count(25)->create();

        $component = Livewire::actingAs($user)->test(History::class);

        $this->assertCount(20, $component->trainings);
        $this->assertTrue($component->hasMore);

        $component->call('loadMore');

        $this->assertCount(25, $component->trainings);
    }

    public function test_a_non_scheduled_training_shows_no_action_buttons(): void
    {
        $user = User::factory()->create();
        $training = Training::factory()->completed()->create();

        Livewire::actingAs($user)
            ->test(History::class)
            ->assertDontSee('Join')
            ->assertDontSee('Leave')
            ->assertDontSee('Edit');
    }
}
