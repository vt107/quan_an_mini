# Quán Ăn Mini

Website giới thiệu **một** quán ăn: landing page có thực đơn + trang quản trị. Bản rút gọn của dự án `nha_hang` (không có QR gọi món, bàn, order, bếp, thanh toán, đặt bàn). Laravel 13, MySQL 8.4, Filament 5 (admin tại `/admin`).

## Môi trường

Chạy hoàn toàn bằng Docker. PHP trên máy host là 8.0 nên **không** chạy `php` / `composer` trực tiếp trên host (npm thì chạy trên host).

- `make up` / `make down`: nginx (listen 80 trong container, map ra host bằng `APP_PORT`, dev = 8092), php-fpm `app`, MySQL (:3310 trên host)
- `make artisan c="..."`, `make composer c="..."`, `make test`, `make fresh` (migrate:fresh --seed)
- `npm run build` / `npm run dev` cho asset Vite (Tailwind 4)
- Test chạy trên database MySQL riêng `quan_an_mini_test`, cache file thật (bắt lỗi serialize như production)
- Tài khoản seed: `admin@quanan.test` / `password`. Production tạo admin bằng `php artisan app:create-admin`
- Không có Redis / queue worker / scheduler: `CACHE_STORE=file`, `SESSION_DRIVER=database`, `QUEUE_CONNECTION=sync`
- **Production**: `compose.prod.yaml` + `docker/prod/` (nginx cổng 80 / HTTPS Let's Encrypt, app, MySQL, certbot, backup), `make prod-*`, `./deploy.sh`. Hướng dẫn: [docs/deploy.md](docs/deploy.md)

## Chức năng

- Trang chủ `/` (`public/home.blade.php`): đầu trang (ảnh nền, nút), món nổi bật, thực đơn theo danh mục, giới thiệu, liên hệ.
- Admin: Tổng quan (thống kê thực đơn), Danh mục, Món ăn, Cài đặt (tab Thương hiệu / Trang chủ / Liên hệ / SEO). Mọi user đang hoạt động (`is_active`) đều là admin; không có quản lý tài khoản trong giao diện.
- SEO: `partials/head` (title, description, Open Graph, JSON-LD Restaurant, Google Analytics), `robots.txt` / `sitemap.xml` động (`Site/SeoController`), bật / tắt index trong Cài đặt.

## Chế độ demo (chỉ xem)

Bản giới thiệu cho khách xem: `DEMO_MODE=true` (`config/demo.php`, mặc định tắt; `.env` local đang bật).

- Mọi lệnh ghi SQL từ request web bị chặn (`App\Support\Demo\DemoMode`, `DB::beforeExecuting`), trừ `sessions` / `cache` và câu khớp `allowed_write_patterns` (remember_token, `last_login_at` khi đăng nhập). Form POST ngoài `allowed_routes` bị chặn trước controller; Livewire / Filament hiện toast `demo-blocked`; upload file dừng ở `_startUpload`. Artisan / test không bị chặn.
- Nút "Demo" nổi (`resources/views/demo/widget.blade.php`, middleware toàn cục `InjectDemoWidget`) liệt kê khu vực + tài khoản trong `demo.portals`; login Filament điền sẵn (`App\Filament\Pages\Auth\Login`, `?demo=<key>`), `/demo/switch/{key}` đổi tài khoản.
- Tài khoản demo: `admin@quanan.test` / `password` (chủ quán, vào `/admin`).
- Dữ liệu: `php artisan demo:reset --force` (= `migrate:fresh --seed`, chỉ chạy khi demo bật). `DatabaseSeeder` gọi `DemoSeeder` thay `MenuSeeder` khi demo bật: quán mẫu "Bếp Nhà Mây", ảnh trong `database/seeders/demo` (CC0 / public domain, nguồn ở `sources.json`), SEO tắt index. Lịch reset hằng ngày `DEMO_RESET_AT` ở `routes/console.php` (cần cron chạy scheduler hoặc gọi thẳng lệnh, xem docs/deploy.md).
- Toggle trong bảng Filament bị tắt ở bản demo (`->disabled(DemoMode::enabled())`) để công tắc không lệch với DB.
- Thêm tính năng mới: thao tác có tác dụng ngoài DB (gửi mail / Telegram, gọi API, xoá file, xoá cache, chạy lệnh) phải gọi `DemoMode::abortIfEnabled()` đầu action; lệnh ghi bắt buộc khi chỉ xem trang thì whitelist hẹp trong `config/demo.php` hoặc `DemoMode::bypass()`; bổ sung dữ liệu mẫu vào `DemoSeeder` + test trong `tests/Feature/DemoModeTest.php`.

## Quy ước

- `menu_items.is_active` = ẩn / hiện trên web; `is_available` = còn / tạm hết (vẫn hiện, có nhãn "Tạm hết"); `is_featured` = mục "Món nổi bật". Web dùng scope `visible()`.
- Thực đơn đọc qua `App\Services\Menu\MenuCatalog` (cache file). Model event của `Category` / `MenuItem` gọi `MenuCatalog::flush()`.
- **Không cache object / model**: `cache.serializable_classes = false` (mặc định Laravel 13). Cache mảng thuộc tính rồi `hydrate()` lại.
- Cấu hình trong bảng `settings` (`Setting::get('group.key')`), luôn 2 cấp group.key. View đọc qua `App\Support\Site` (có giá trị mặc định), nhận sẵn biến `$site` (view composer); không gọi `Setting::get` trực tiếp trong view.
- Màu chủ đạo: giao diện viết bằng class `amber-*` của Tailwind; `partials/head` ghi đè biến `--color-amber-*` bằng sắc độ trộn từ màu admin chọn. Filament nhận màu qua `Color::hex()`.
- Scope trên model dùng `$query->qualifyColumn(...)`.
- Tiền là VND, số nguyên (`App\Support\Money::format`).
- Model dùng attribute `#[Fillable]` / `#[Hidden]` / `#[Scope]` như skeleton Laravel 13. Nhãn hiển thị bằng tiếng Việt.
