<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('imports:dispatch-due')
    ->everyMinute()
    ->withoutOverlapping(5);

Schedule::command('queue:work --queue=imports --stop-when-empty --max-time=600 --timeout=600 --tries=2')
    ->everyMinute()
    ->withoutOverlapping(15);

Schedule::command('analytics:prune-outbound-clicks')
    ->dailyAt('02:30')
    ->timezone('UTC')
    ->withoutOverlapping(60);

Schedule::command('analytics:prune-vacancy-detail-views')
    ->dailyAt('02:35')
    ->timezone('UTC')
    ->withoutOverlapping(60);
