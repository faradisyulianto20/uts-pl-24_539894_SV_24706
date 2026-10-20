#!/bin/sh
set -e

# Pastikan symbolic link storage publik terhubung
echo "==> Menghubungkan symbolic link storage..."
php artisan storage:link || true

# Tunggu database siap dan jalankan migrasi database otomatis saat runtime
echo "==> Memeriksa koneksi basis data & menjalankan migrasi..."
php artisan migrate --force --isolated || true

# Jalankan command utama container (frankenphp run)
echo "==> Menjalankan FrankenPHP server..."
exec "$@"
