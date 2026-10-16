<?php

use App\Console\Commands\SendMeetingReminders;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Send meeting reminders every 15 minutes (24h-before and 1h-before windows)
Schedule::command(SendMeetingReminders::class)->everyFifteenMinutes();
