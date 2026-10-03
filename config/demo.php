<?php

/*
|--------------------------------------------------------------------------
| Chế độ demo (chỉ xem)
|--------------------------------------------------------------------------
|
| Bật DEMO_MODE=true khi deploy bản giới thiệu cho khách xem:
| - Mọi lệnh ghi SQL phát sinh từ request web bị chặn (App\Support\Demo\DemoMode),
|   trừ các bảng hạ tầng trong `writable_tables` và các câu lệnh khớp `allowed_write_patterns`.
| - Form POST / PUT / PATCH / DELETE bị chặn trước khi vào controller, trừ `allowed_routes`.
| - Nút "Demo" nổi ở góc màn hình liệt kê các khu vực + tài khoản đăng nhập sẵn (`portals`).
| - `php artisan demo:reset` dựng lại database với dữ liệu mẫu (lịch chạy hằng ngày).
|
| Lệnh artisan / queue / scheduler / test (chạy CLI) không bị chặn.
|
*/

return [

    'enabled' => (bool) env('DEMO_MODE', false),

    'message' => 'Đây là bản demo chỉ xem: thao tác thêm / sửa / xoá đã được tắt.',

    // Giờ dựng lại dữ liệu demo mỗi ngày (múi giờ app). null = không tự reset.
    'reset_at' => env('DEMO_RESET_AT', '04:00'),

    // Bảng hạ tầng vẫn cho ghi (phiên đăng nhập, cache, rate limit).
    'writable_tables' => [
        'sessions',
        'cache',
        'cache_locks',
    ],

    // Regex (so với câu SQL) cho phép ghi dù bảng không nằm trong writable_tables.
    'allowed_write_patterns' => [
        // "Ghi nhớ đăng nhập"
        '/^update [`"]?users[`"]? set [`"]?remember_token[`"]? = \?/i',
        // Ghi thời điểm đăng nhập (listener Login trong AppServiceProvider), không có thì đăng nhập bị chặn
        '/^update [`"]?users[`"]? set [`"]?last_login_at[`"]? = \?, [`"]?users[`"]?\.[`"]?updated_at[`"]? = \? where [`"]?id[`"]? = \?$/i',
    ],

    // Route (tên hoặc pattern path) được phép nhận POST / PUT / PATCH / DELETE.
    // Request Livewire (livewire.update) luôn đi qua, lệnh ghi bên trong do guard SQL chặn.
    // Đăng nhập Filament đi qua Livewire nên chỉ cần route đăng xuất.
    'allowed_routes' => [
        'filament.admin.auth.logout',
    ],

    /*
     * Các khu vực hiển thị trong nút Demo.
     * url / login_url là path tương đối (qua helper url()).
     * accounts[].key là duy nhất toàn file: /login?demo=<key> sẽ điền sẵn tài khoản đó.
     */
    'portals' => [
        [
            'label' => 'Trang chủ quán',
            'description' => 'Website khách xem: ảnh đầu trang, món nổi bật, thực đơn theo danh mục (có nhãn "Tạm hết"), giới thiệu, liên hệ, nút gọi / Zalo.',
            'url' => '/',
            'accounts' => [],
        ],
        [
            'label' => 'Trang quản trị',
            'description' => 'Tổng quan thực đơn, quản lý danh mục / món ăn (ẩn hiện, còn / hết, nổi bật, thùng rác), cài đặt thương hiệu, trang chủ, liên hệ, SEO.',
            'url' => '/admin',
            'login_url' => '/admin/login',
            'accounts' => [
                ['key' => 'admin', 'role' => 'Chủ quán (quản trị)', 'email' => 'admin@quanan.test', 'password' => 'password'],
            ],
        ],
    ],

];
