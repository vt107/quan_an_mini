<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            'restaurant.name' => 'Quán Ăn Mini',
            'restaurant.slogan' => 'Món Việt đậm vị, nguyên liệu tươi mỗi ngày',
            'restaurant.phone' => '0900 000 000',
            'restaurant.address' => '123 Đường ABC, Quận 1, TP.HCM',
            'restaurant.opening_hours' => '10:00 - 22:00',
            'brand.color' => '#d97706',
            'home.show_featured' => true,
            'seo.allow_indexing' => true,
        ];

        foreach ($defaults as $name => $value) {
            [$group, $key] = explode('.', $name, 2);

            Setting::firstOrCreate(['group' => $group, 'key' => $key], ['value' => $value]);
        }
    }
}
