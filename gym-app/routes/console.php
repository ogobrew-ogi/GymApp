<?php

declare(strict_types=1);

use App\Console\Commands\CompletePastTrainings;
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
| Architecture Spec §7: the one recurring job this application needs —
| transitioning past-due `scheduled` trainings to `completed`. Requires
| `php artisan schedule:run` to be invoked every minute by the host's
| cron (or equivalent) in every environment.
|
*/

Schedule::command(CompletePastTrainings::class)->everyFiveMinutes();

