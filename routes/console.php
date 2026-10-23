<?php

use Illuminate\Support\Facades\Schedule;

// Detects offline devices, low stock, overdue inspections, etc. Run `php artisan schedule:work` in development.
Schedule::command('aquatrack:check')->everyFiveMinutes();
