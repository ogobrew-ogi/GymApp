<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\ClosureReason;
use App\Enums\TrainingStatus;
use App\Models\GymClosure;
use App\Models\Training;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

/**
 * Demo dataset covering the app's major business cases end to end — not
 * exhaustive test fixtures, just enough to manually walk every screen and
 * state in the UX/UI Spec without hand-creating data first. Intended to
 * run against a fresh database (`php artisan migrate:fresh --seed`); it
 * does not guard against re-running on an already-seeded one.
 */
final class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::factory()->admin()->create([
            'name' => 'Admin',
            'username' => 'admin',
            'must_change_password' => false,
        ]);

        [$alice, $bob, $carol, $dave, $erin] = User::factory()
            ->count(5)
            ->sequence(
                ['name' => 'Alice Ivanova', 'username' => 'alice'],
                ['name' => 'Bob Petrov', 'username' => 'bob'],
                ['name' => 'Carol Georgieva', 'username' => 'carol'],
                ['name' => 'Dave Dimitrov', 'username' => 'dave'],
                ['name' => 'Erin Nikolova', 'username' => 'erin'],
            )
            ->state(['must_change_password' => false])
            ->create();

        // Multi-participant, scheduled — owner can only Cancel, not
        // Delete, once others have joined (Domain Model Spec §5.2).
        $multiParticipant = Training::factory()->create([
            'owner_id' => $alice->id,
            ...$this->window(days: 1, hour: 18, durationMinutes: 60),
            'max_participants' => 6,
            'note' => 'Evening sparring session',
        ]);
        $this->join($multiParticipant, $alice);
        $this->join($multiParticipant, $bob);
        $this->join($multiParticipant, $carol);

        // Solo owner, scheduled — the one state where Delete (not
        // Cancel) is available.
        $soloOwner = Training::factory()->create([
            'owner_id' => $bob->id,
            ...$this->window(days: 2, hour: 7, durationMinutes: 90),
            'max_participants' => 10,
            'note' => null,
        ]);
        $this->join($soloOwner, $bob);

        // At capacity — demonstrates the "Full" badge and that Join is
        // unavailable once max_participants is reached.
        $full = Training::factory()->create([
            'owner_id' => $carol->id,
            ...$this->window(days: 3, hour: 19, durationMinutes: 60),
            'max_participants' => 2,
            'note' => 'Beginners welcome',
        ]);
        $this->join($full, $carol);
        $this->join($full, $dave);

        // Completed — already in the past, in the same terminal state the
        // trainings:complete-past scheduled command would leave it in
        // (Architecture Spec §7), demonstrating the History screen.
        $completed = Training::factory()->create([
            'owner_id' => $dave->id,
            ...$this->window(days: -2, hour: 18, durationMinutes: 60),
            'max_participants' => 8,
            'status' => TrainingStatus::Completed,
            'note' => "Last week's conditioning class",
        ]);
        $this->join($completed, $dave);
        $this->join($completed, $erin);

        // Cancelled with participants already joined — stays visible with
        // a Cancelled badge rather than disappearing (Project Brief,
        // "Business Rules").
        $cancelled = Training::factory()->create([
            'owner_id' => $erin->id,
            ...$this->window(days: 4, hour: 20, durationMinutes: 60),
            'max_participants' => 6,
            'status' => TrainingStatus::Cancelled,
            'note' => 'Cancelled — instructor unavailable',
        ]);
        $this->join($cancelled, $erin);
        $this->join($cancelled, $alice);

        // One upcoming closure — blocks new trainings from being created
        // in its window (Project Brief, "Gym Rules").
        GymClosure::factory()->create([
            'starts_at' => CarbonImmutable::now()->addDays(6)->startOfDay(),
            'ends_at' => CarbonImmutable::now()->addDays(8)->startOfDay(),
            'reason' => ClosureReason::Maintenance,
            'note' => 'Floor resurfacing',
            'created_by' => $admin->id,
        ]);
    }

    /**
     * @return array{date: string, start_time: CarbonImmutable, end_time: CarbonImmutable}
     */
    private function window(int $days, int $hour, int $durationMinutes): array
    {
        $start = CarbonImmutable::now()->addDays($days)->setTime($hour, 0);

        return [
            'date' => $start->toDateString(),
            'start_time' => $start,
            'end_time' => $start->addMinutes($durationMinutes),
        ];
    }

    private function join(Training $training, User $user): void
    {
        $training->trainingParticipants()->create([
            'user_id' => $user->id,
            'joined_at' => CarbonImmutable::now(),
        ]);
    }
}
