<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class MenuSeeder extends Seeder
{
    public function run(): void
    {
        $menu = [
            'Khai vị' => [
                ['Gỏi cuốn tôm thịt', 45000],
                ['Chả giò rế', 55000],
                ['Gỏi ngó sen tôm thịt', 85000],
            ],
            'Món chính' => [
                ['Phở bò tái nạm', 65000, 'Nước dùng xương bò ninh 12 tiếng, bánh phở tươi.', true],
                ['Cơm tấm sườn bì chả', 60000, 'Sườn nướng than hoa, bì, chả trứng, mỡ hành.', true],
                ['Bún chả Hà Nội', 60000],
                ['Cá kho tộ', 120000],
                ['Gà nướng mật ong', 180000],
            ],
            'Lẩu' => [
                ['Lẩu thái hải sản', 350000, 'Tôm, mực, nghêu, cá; vị chua cay đậm đà. Dành cho 3-4 người.', true],
                ['Lẩu gà lá é', 320000],
            ],
            'Đồ uống' => [
                ['Trà đá', 5000],
                ['Cà phê sữa đá', 30000],
                ['Nước cam ép', 40000],
                ['Bia Sài Gòn', 25000],
            ],
            'Tráng miệng' => [
                ['Chè khúc bạch', 35000],
                ['Bánh flan', 20000],
            ],
        ];

        $categoryOrder = 0;

        foreach ($menu as $categoryName => $items) {
            $category = Category::firstOrCreate(
                ['slug' => Str::slug($categoryName)],
                ['name' => $categoryName, 'sort_order' => $categoryOrder++],
            );

            foreach ($items as $index => $item) {
                [$name, $price] = $item;

                $category->menuItems()->firstOrCreate(
                    ['slug' => Str::slug($name)],
                    [
                        'name' => $name,
                        'price' => $price,
                        'description' => $item[2] ?? null,
                        'is_featured' => $item[3] ?? false,
                        'sort_order' => $index,
                    ],
                );
            }
        }
    }
}
