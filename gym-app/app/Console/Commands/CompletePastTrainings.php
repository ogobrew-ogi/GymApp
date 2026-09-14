<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\TrainingLifecycleService;
use Illuminate\Console\Command;

final class CompletePastTrainings extends Command
{
    protected $signature = 'trainings:complete-past';

    protected $description = 'Transition scheduled trainings whose end time has passed to completed.';

    public function handle(TrainingLifecycleService $lifecycle): int
    {
        $count = $lifecycle->completePast();

        $this->info("Completed {$count} training(s).");

        return self::SUCCESS;
    }
}
