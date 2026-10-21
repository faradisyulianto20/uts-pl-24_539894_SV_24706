#!/bin/sh
set -e

# Pastikan symbolic link storage publik terhubung
echo "==> Menghubungkan symbolic link storage..."
php artisan storage:link || true

# Tunggu database siap dan jalankan migrasi database otomatis saat runtime
echo "==> Memeriksa koneksi basis data & menjalankan migrasi..."
php artisan migrate --force || true

# Jalankan seeder awal (idempoten) agar data admin & artikel demo tersedia
echo "==> Menjalankan seeder basis data..."
php artisan db:seed --force || true

# Jalankan command utama container (frankenphp run)
echo "==> Menjalankan FrankenPHP server..."
exec "$@"
