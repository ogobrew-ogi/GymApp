<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Training;
use App\Models\TrainingParticipant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TrainingParticipant>
 */
final class TrainingParticipantFactory extends Factory
{
    protected $model = TrainingParticipant::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'training_id' => Training::factory(),
            'user_id' => User::factory(),
            'joined_at' => fake()->dateTimeBetween('-30 days', 'now'),
        ];
    }
}
