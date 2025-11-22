<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/* Schedule console jobs from cron task:
 *    * * * * *  docker exec -w /var/www/html/home-monitor php_apache php artisan schedule:run >> /dev/null 2>&1
 */

// External data fetch
Schedule::command('device:fetch-octopus-data')
    ->timezone('UTC')
    ->dailyAt('01:05')
    ->withoutOverlapping()
    ->runInBackground();
