# Triển khai production

Hệ thống chạy bằng Docker Compose trên một máy chủ Linux: `compose.prod.yaml`.

| Dịch vụ | Việc |
|---|---|
| `nginx` | Cổng 80 (và 443 khi có SSL), trả asset tĩnh / ảnh upload, chuyển PHP sang `app` |
| `app` | php-fpm (Laravel) |
| `mysql` | Dữ liệu. Không mở cổng ra ngoài |
| `certbot` | Gia hạn chứng chỉ Let's Encrypt mỗi 12 giờ |
| `backup` | Sao lưu database + ảnh upload mỗi ngày vào `./backups` |

Code, thư viện PHP và asset được đóng gói sẵn trong image (`docker/prod/Dockerfile`), không mount source vào container.

## 1. Chuẩn bị máy chủ

- Ubuntu 22.04 / 24.04, tối thiểu 1 vCPU, 1 GB RAM, 10 GB ổ đĩa.
- Cài Docker: `curl -fsSL https://get.docker.com | sh`
- Mở cổng 80 và 443 trên firewall (`ufw allow 80,443/tcp`).
- Trỏ bản ghi DNS `A` của domain về IP máy chủ.

## 2. Cài đặt lần đầu

```bash
git clone <repo> quan_an_mini && cd quan_an_mini
cp .env.production.example .env
nano .env        # điền mọi giá trị <...>: domain, mật khẩu DB...
```

Tạo `APP_KEY` rồi dán vào `.env`:

```bash
docker compose -f compose.prod.yaml build
docker compose -f compose.prod.yaml run --rm --no-deps app php artisan key:generate --show
```

Khởi động, tạo bảng và tài khoản admin:

```bash
make prod-up
make prod-artisan c="migrate --force"
make prod-artisan c="db:seed --class=SettingSeeder --force"
make prod-artisan c="app:create-admin"       # nhập họ tên, email, mật khẩu
```

Muốn có thực đơn mẫu để thử: `make prod-artisan c="db:seed --class=MenuSeeder --force"`.

Lúc này trang đã chạy ở `http://<domain>`, trang quản trị ở `http://<domain>/admin`.

## 3. Bật HTTPS

Cần `SERVER_NAME` và `LETSENCRYPT_EMAIL` trong `.env`, domain đã trỏ về máy chủ, cổng 80 mở:

```bash
make prod-ssl
```

Nginx tự chuyển sang cấu hình HTTPS khi thấy chứng chỉ: cổng 80 chuyển hướng sang 443, có HTTP/2 + HSTS. `certbot` tự gia hạn, nginx tự nạp lại chứng chỉ mỗi 6 giờ.

Sau khi có HTTPS: `APP_URL=https://<domain>`, `SESSION_SECURE_COOKIE=true`, rồi `make prod-up`.

**Chạy sau Cloudflare / load balancer** (SSL kết thúc ở proxy): bỏ qua `make prod-ssl`, đặt `TRUSTED_PROXIES=*` và `APP_URL=https://<domain>`.

## 4. Cấu hình trong trang quản trị

Đăng nhập `https://<domain>/admin`:

1. **Cài đặt**: tên quán, logo, màu chủ đạo, nội dung trang chủ, liên hệ, SEO.
2. **Danh mục**, **Món ăn**: nhập thực đơn, ảnh món, đánh dấu món nổi bật / tạm hết.

## 5. Cập nhật phiên bản mới

```bash
./deploy.sh        # hoặc make prod-deploy
```

Script: kéo code mới, build image, sao lưu database, khởi động lại, chạy migrate.

## 6. Sao lưu & khôi phục

- Tự động mỗi ngày lúc `BACKUP_TIME` (mặc định 03:00), giữ `BACKUP_KEEP_DAYS` ngày (mặc định 14), trong `BACKUP_DIR` (mặc định `./backups`): `backups/db/*.sql.gz` (database), `backups/uploads/*.tar.gz` (ảnh).
- Sao lưu ngay: `make prod-backup`
- Khôi phục database: `make prod-restore file=quan_an_mini-20260928-030000.sql.gz` (ghi đè dữ liệu hiện tại).
- Khôi phục ảnh: `docker compose -f compose.prod.yaml run --rm --entrypoint sh -v "$PWD/backups:/backups" app -c "tar -xzf /backups/uploads/<file> -C /var/www/html/storage/app"`

Thư mục `backups` nằm trên cùng máy chủ: **nên đồng bộ thêm ra ngoài** (rclone lên Google Drive / S3, hoặc snapshot của nhà cung cấp VPS).

## 7. Vận hành

| Việc | Lệnh |
|---|---|
| Trạng thái dịch vụ | `make prod-ps` |
| Xem log | `make prod-logs` |
| Lệnh artisan | `make prod-artisan c="..."` |
| Đặt lại mật khẩu admin | `make prod-artisan c="app:create-admin"` (nhập lại email cũ) |
| Bảo trì | `make prod-artisan c="down"` / `c="up"` |

## 8. Bản demo chỉ xem (tuỳ chọn)

Dùng khi deploy bản giới thiệu cho khách xem (quán mẫu, không ghi được dữ liệu):

1. `.env`: `DEMO_MODE=true`, `DEMO_RESET_AT=04:00`, `MAIL_MAILER=log`. Không điền secret thật.
2. Nạp dữ liệu mẫu: `make prod-artisan c="demo:reset --force"` (xoá toàn bộ database rồi seed lại).
3. Làm mới dữ liệu mỗi ngày: image production không chạy scheduler, thêm cron trên máy chủ:

   ```cron
   0 4 * * * cd /đường-dẫn/quan_an_mini && docker compose -f compose.prod.yaml exec -T app sh -c "php artisan demo:reset --force && php artisan optimize && php artisan filament:optimize" >/dev/null 2>&1
   ```

   (`demo:reset` chạy `optimize:clear` nên chạy lại `optimize` cho nhanh.) Bản demo đặt "Cho phép Google tìm thấy website" = tắt (`robots.txt` chặn toàn bộ).

