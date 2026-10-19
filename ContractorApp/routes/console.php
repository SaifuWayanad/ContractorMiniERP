<?php

use App\Console\Commands\SendMeetingReminders;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Daily 6 AM meeting digest. Runs every 15 min; the command guards on hour === 6.
// withoutOverlapping() prevents a stale run from firing a second digest in the same window.
Schedule::command(SendMeetingReminders::class)
    ->everyFifteenMinutes()
    ->withoutOverlapping(20);
