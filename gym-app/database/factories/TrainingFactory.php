<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\TrainingStatus;
use App\Models\Training;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Training>
 */
final class TrainingFactory extends Factory
{
    protected $model = Training::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = CarbonImmutable::instance(fake()->dateTimeBetween('+1 day', '+30 days'))
            ->setTime(fake()->numberBetween(7, 20), fake()->randomElement([0, 15, 30, 45]));

        $end = $start->addMinutes(fake()->randomElement([60, 90, 120]));

        return [
            'owner_id' => User::factory(),
            'date' => $start->toDateString(),
            'start_time' => $start,
            'end_time' => $end,
            'max_participants' => fake()->numberBetween(4, 12),
            'note' => fake()->optional()->sentence(),
            'status' => TrainingStatus::Scheduled,
        ];
    }

    /**
     * A window that has already fully passed — the raw material for
     * `completed()`, and useful on its own for exercising the
     * `trainings:complete-past` command against real data.
     */
    public function past(): static
    {
        return $this->state(function (): array {
            $start = CarbonImmutable::instance(fake()->dateTimeBetween('-30 days', '-1 day'))
                ->setTime(fake()->numberBetween(7, 20), 0);

            return [
                'date' => $start->toDateString(),
                'start_time' => $start,
                'end_time' => $start->addHour(),
            ];
        });
    }

    /**
     * Terminal state reached via the scheduled command in normal
     * operation — set directly here since factories build data, not
     * behavior (Domain Model Spec §5.2).
     */
    public function completed(): static
    {
        return $this->past()->state([
            'status' => TrainingStatus::Completed,
        ]);
    }

    public function cancelled(): static
    {
        return $this->state([
            'status' => TrainingStatus::Cancelled,
        ]);
    }
}
