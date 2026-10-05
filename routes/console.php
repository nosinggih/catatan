<?php

use Illuminate\Support\Facades\Schedule;

// Needs a cron entry on the server: * * * * * php artisan schedule:run
Schedule::command('catatan:send-reminders')->hourly();
