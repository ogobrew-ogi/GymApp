<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\TrainingLifecycleService;
use Illuminate\Console\Command;

final class PurgePastTrainings extends Command
{
    protected $signature = 'trainings:purge-past';

    protected $description = 'Delete trainings whose end time has passed (no history is kept).';

    public function handle(TrainingLifecycleService $lifecycle): int
    {
        $count = $lifecycle->purgePast();

        $this->info("Deleted {$count} past training(s).");

        return self::SUCCESS;
    }
}
