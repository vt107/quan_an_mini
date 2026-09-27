<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/** Tài khoản admin mẫu cho môi trường dev (production dùng: php artisan app:create-admin). */
class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(['email' => 'admin@quanan.test'], [
            'name' => 'Quản trị',
            'password' => 'password',
            'email_verified_at' => now(),
        ]);
    }
}
