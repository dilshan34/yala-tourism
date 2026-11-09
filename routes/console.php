<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('calendar:sync-all')->everyFifteenMinutes();
Schedule::command('operations:check-reminders')->dailyAt('06:00');
