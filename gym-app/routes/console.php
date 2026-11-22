<?php

declare(strict_types=1);

use App\Console\Commands\GenerateRecurringTrainings;
use App\Console\Commands\PurgePastTrainings;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduled Tasks
|--------------------------------------------------------------------------
|
| Requires `php artisan schedule:run` to be invoked every minute by the
| host's cron (or `php artisan schedule:work` locally).
|
| - Ended trainings are deleted; no history is kept.
| - Admins' weekly slots are turned into trainings as the 30-day
|   booking window moves forward.
|
*/

Schedule::command(PurgePastTrainings::class)->everyFiveMinutes();

Schedule::command(GenerateRecurringTrainings::class)->hourly();

