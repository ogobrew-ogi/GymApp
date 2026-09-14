<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ClosureReason;
use App\Models\GymClosure;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GymClosure>
 */
final class GymClosureFactory extends Factory
{
    protected $model = GymClosure::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = CarbonImmutable::instance(fake()->dateTimeBetween('+1 day', '+60 days'))
            ->setTime(0, 0);

        return [
            'starts_at' => $start,
            'ends_at' => $start->addDays(fake()->numberBetween(1, 5)),
            'reason' => fake()->randomElement(ClosureReason::cases()),
            'note' => fake()->optional()->sentence(),

            // Only an admin can create a closure (Architecture Spec §6).
            'created_by' => User::factory()->admin(),
        ];
    }
}
