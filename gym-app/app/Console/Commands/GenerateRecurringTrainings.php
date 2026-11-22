<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\RecurringTrainingService;
use Illuminate\Console\Command;

final class GenerateRecurringTrainings extends Command
{
    protected $signature = 'trainings:generate-recurring';

    protected $description = "Create trainings from admins' weekly slots as the booking window moves forward.";

    public function handle(RecurringTrainingService $recurring): int
    {
        $count = $recurring->generateAll();

        $this->info("Created {$count} regular training(s).");

        return self::SUCCESS;
    }
}
