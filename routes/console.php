<?php

use App\Support\Demo\DemoMode;
use Illuminate\Support\Facades\Schedule;

// Bản demo (DEMO_MODE=true): dựng lại dữ liệu mẫu mỗi ngày. Cần chạy scheduler (cron `php artisan schedule:run` mỗi phút).
if ($resetAt = config('demo.reset_at')) {
    Schedule::command('demo:reset --force')
        ->dailyAt($resetAt)
        ->when(fn () => DemoMode::enabled())
        ->withoutOverlapping();
}
