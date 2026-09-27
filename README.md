# Quán Ăn Mini

Website giới thiệu quán ăn: landing page có thực đơn + trang quản trị (món ăn, cài đặt thương hiệu / trang chủ / SEO).

Laravel 13 · MySQL 8.4 · Filament 5 · Nginx (listen 80)

## Chạy lần đầu

```bash
cp .env.example .env
make build && make up
make composer c="install"
make artisan c="key:generate"
make fresh                    # migrate + seed dữ liệu mẫu
make artisan c="storage:link"
npm install && npm run build
```

- Web: http://localhost:8092 · Admin: http://localhost:8092/admin (`admin@quanan.test` / `password`)
- Test: `make test`

## Triển khai production

Docker Compose (`compose.prod.yaml`): nginx cổng 80 / HTTPS Let's Encrypt, backup tự động, `./deploy.sh` để cập nhật. Xem [docs/deploy.md](docs/deploy.md).

Quy ước code: xem [CLAUDE.md](CLAUDE.md).
