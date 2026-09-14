<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<User>
 */
final class UserFactory extends Factory
{
    protected $model = User::class;

    /**
     * Shared hash across every factory-created user so seeding stays fast
     * (bcrypt is deliberately slow — hashing once and reusing avoids
     * paying that cost per row).
     */
    protected static ?string $hashedPassword = null;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'username' => fake()->unique()->userName(),
            'password' => self::$hashedPassword ??= Hash::make('password'),

            // Mirrors the migration default: every account starts as a
            // regular, active member that must change its password on
            // first login (Domain Model Spec §5.1).
            'is_admin' => false,
            'is_active' => true,
            'must_change_password' => true,
        ];
    }

    /**
     * Admin role — derived from is_admin, never independently stored
     * (Domain Model Spec §5.1).
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_admin' => true,
        ]);
    }
}
