<?php

declare(strict_types=1);

namespace Tests\Feature\Trainings;

use App\Livewire\Trainings\TrainingList;
use App\Models\Training;
use App\Models\User;
use App\Support\LocalTime;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

final class DateFilterTest extends TestCase
{
    use RefreshDatabase;

    private function trainingOn(string $date, string $startTime = '18:00'): Training
    {
        return Training::factory()->create([
            'date' => $date,
            'start_time' => LocalTime::toUtc($date, $startTime),
            'end_time' => LocalTime::toUtc($date, '19:00'),
        ]);
    }

    public function test_selecting_a_date_shows_only_that_days_trainings(): void
    {
        $user = User::factory()->create();
        $today = CarbonImmutable::now(LocalTime::DISPLAY_TIMEZONE)->toDateString();
        $tomorrow = CarbonImmutable::now(LocalTime::DISPLAY_TIMEZONE)->addDay()->toDateString();

        $todayTraining = $this->trainingOn($today);
        $tomorrowTraining = $this->trainingOn($tomorrow);

        $shown = Livewire::actingAs($user)
            ->test(TrainingList::class)
            ->set('selectedDate', $tomorrow)
            ->trainings;

        $ids = $shown->pluck('id')->all();
        $this->assertContains($tomorrowTraining->id, $ids);
        $this->assertNotContains($todayTraining->id, $ids);
    }

    public function test_clearing_the_filter_restores_the_full_list(): void
    {
        $user = User::factory()->create();
        $today = CarbonImmutable::now(LocalTime::DISPLAY_TIMEZONE)->toDateString();
        $tomorrow = CarbonImmutable::now(LocalTime::DISPLAY_TIMEZONE)->addDay()->toDateString();

        $todayTraining = $this->trainingOn($today);
        $tomorrowTraining = $this->trainingOn($tomorrow);

        $shown = Livewire::actingAs($user)
            ->test(TrainingList::class)
            ->set('selectedDate', $tomorrow)
            ->call('clearDateFilter')
            ->trainings;

        $ids = $shown->pluck('id')->all();
        $this->assertContains($todayTraining->id, $ids);
        $this->assertContains($tomorrowTraining->id, $ids);
    }

    public function test_a_date_with_nothing_scheduled_shows_the_empty_state(): void
    {
        $user = User::factory()->create();
        $today = CarbonImmutable::now(LocalTime::DISPLAY_TIMEZONE)->toDateString();
        $emptyDate = CarbonImmutable::now(LocalTime::DISPLAY_TIMEZONE)->addDays(5)->toDateString();

        $this->trainingOn($today);

        Livewire::actingAs($user)
            ->test(TrainingList::class)
            ->set('selectedDate', $emptyDate)
            ->assertSee('No trainings on this date.');
    }

    public function test_selectable_range_spans_today_through_thirty_days_out(): void
    {
        $user = User::factory()->create();
        $today = CarbonImmutable::now(LocalTime::DISPLAY_TIMEZONE)->toDateString();
        $thirtyDaysOut = CarbonImmutable::now(LocalTime::DISPLAY_TIMEZONE)->addDays(30)->toDateString();

        Livewire::actingAs($user)
            ->test(TrainingList::class)
            ->assertSet('minSelectableDate', $today)
            ->assertSet('maxSelectableDate', $thirtyDaysOut);
    }
}
