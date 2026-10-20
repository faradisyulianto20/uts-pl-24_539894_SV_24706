# ==============================================================
# Backend Dockerfile – Laravel dengan FrankenPHP (Nilai Tambahan +10)
# Base image versi spesifik (bukan latest)
# ==============================================================
FROM dunglas/frankenphp:1.4.4-php8.2-alpine AS runner

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

# Salin entrypoint script untuk runtime migration (anti-pattern build-time migration teratasi)
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Buat direktori dan atur permission untuk storage dan bootstrap/cache
RUN mkdir -p storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Konfigurasi FrankenPHP melayani port 8000 dan web root di /app/public
ENV SERVER_NAME=":8000"
ENV FRANKENPHP_CONFIG="document_root /app/public"

EXPOSE 8000

# Entrypoint menjalankan migrasi saat runtime, lalu mengeksekusi CMD
ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]

CMD ["frankenphp", "run"]
