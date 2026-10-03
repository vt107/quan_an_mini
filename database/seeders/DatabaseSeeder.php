<?php

namespace Database\Seeders;

use App\Support\Demo\DemoMode;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            SettingSeeder::class,
            UserSeeder::class,
            // Bản demo (DEMO_MODE=true): thực đơn + cài đặt mẫu đầy đủ thay cho thực đơn tối thiểu
            DemoMode::enabled() ? DemoSeeder::class : MenuSeeder::class,
        ]);
    }
}
