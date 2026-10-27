<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
// FreightLink automation (see config/automation.php)
Schedule::command('automation:run heartbeat')->everyMinute();
Schedule::command('automation:run auto-assign')->everyFiveMinutes()->withoutOverlapping();
Schedule::command('automation:run check-stuck')->hourly()->withoutOverlapping();
Schedule::command('automation:run close-delivered')->dailyAt('02:00');
Schedule::command('automation:run cleanup')->dailyAt('03:00');
Schedule::command('automation:run daily-report')->dailyAt('07:00');
