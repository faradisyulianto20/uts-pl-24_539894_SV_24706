# ==============================================================
# Backend Dockerfile – Laravel dengan FrankenPHP (Nilai Tambahan +10)
# Base image versi spesifik PHP 8.4 (bukan latest)
# ==============================================================
FROM dunglas/frankenphp:1.4.4-php8.4-alpine AS runner

# Install ekstensi PHP yang dibutuhkan Laravel
RUN install-php-extensions \
    pdo_mysql \
    zip \
    intl \
    bcmath \
    pcntl \
    opcache

WORKDIR /app

# Salin binary Composer versi spesifik dari official image
COPY --from=composer:2.7.2 /usr/bin/composer /usr/bin/composer

# Cache layer: salin composer.json dan composer.lock terlebih dahulu
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

# Salin seluruh kode aplikasi backend
COPY . .

# Regenerasi cache bootstrap agar package/route/config cache dari mesin lokal
# (yang mungkin menunjuk paket dev seperti Boost) tidak terbawa ke image produksi.
RUN rm -f bootstrap/cache/*.php \
    && php artisan package:discover --ansi --no-interaction

# Salin entrypoint script untuk runtime migration
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Buat direktori dan atur permission untuk storage dan bootstrap/cache
RUN mkdir -p storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Konfigurasi FrankenPHP melayani port 8000
# Catatan: document root TIDAK diatur lewat FRANKENPHP_CONFIG (bukan subdirektif
# yang valid di blok frankenphp), melainkan oleh Caddyfile bawaan image:
#   root * public/
# Mengisinya akan membuat FrankenPHP gagal listen dan server tidak pernah start.
ENV SERVER_NAME="http://:8000"

EXPOSE 8000

# Entrypoint menjalankan migrasi saat runtime, lalu mengeksekusi CMD
ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]

CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]
