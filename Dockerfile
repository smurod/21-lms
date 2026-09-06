# syntax=docker/dockerfile:1

# =============================================================================
# 21 LMS — production-like image
#   stage 1 (assets)  — Vite/Tailwind build
#   stage 2 (vendor)  — composer install --no-dev
#   stage 3 (app)     — php:8.3-fpm-alpine runtime
# =============================================================================

# ----------------------------------------------------------------------------
# 1. Frontend assets (CSS via Vite)
# ----------------------------------------------------------------------------
FROM node:22-alpine AS assets

WORKDIR /app

COPY package.json package-lock.json .npmrc ./
RUN npm ci --ignore-scripts

COPY vite.config.js postcss.config.js tailwind.config.js ./
COPY resources/css resources/css

RUN npm run build

# ----------------------------------------------------------------------------
# 2. Composer dependencies (no dev)
# ----------------------------------------------------------------------------
FROM composer:2 AS vendor

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install \
        --no-dev \
        --prefer-dist \
        --no-interaction \
        --no-progress \
        --optimize-autoloader \
        --no-scripts

# ----------------------------------------------------------------------------
# 3. Runtime
# ----------------------------------------------------------------------------
FROM php:8.3-fpm-alpine AS app

# --- PHP extensions ---------------------------------------------------------
# phpredis is built from the GitHub tarball: pecl.php.net REST is flaky
# ("does not have REST info xml available") and breaks image builds.
RUN apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS \
        icu-dev \
        libzip-dev \
        oniguruma-dev \
        postgresql-dev \
    && mkdir -p /usr/src/php/ext/redis \
    && curl -fsSL https://github.com/phpredis/phpredis/archive/refs/tags/6.1.0.tar.gz \
        | tar -xz -C /usr/src/php/ext/redis --strip-components=1 \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath \
        intl \
        mbstring \
        opcache \
        pcntl \
        pdo_pgsql \
        pgsql \
        redis \
        zip \
    && apk del .build-deps

# Runtime libs for the compiled extensions + su-exec for privilege drop
RUN apk add --no-cache \
        icu-libs \
        libpq \
        libzip \
        oniguruma \
        su-exec

# --- PHP production settings -------------------------------------------------
COPY docker/php/php.ini docker/php/opcache.ini /usr/local/etc/php/conf.d/

# --- Application code ---------------------------------------------------------
WORKDIR /var/www/html

COPY --from=vendor /app/vendor ./vendor
COPY --from=vendor /usr/bin/composer /usr/local/bin/composer
COPY . .
COPY --from=assets /app/public/build ./public/build

RUN rm -f .env .env.backup .env.production public/hot \
            bootstrap/cache/packages.php bootstrap/cache/services.php \
    && mkdir -p storage/framework/cache/data \
                storage/framework/sessions \
                storage/framework/testing \
                storage/framework/views \
                storage/logs \
                storage/app/public \
                bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R ug+rwX storage bootstrap/cache

# --- Entrypoint ----------------------------------------------------------------
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 9000

HEALTHCHECK --interval=30s --timeout=5s --start-period=60s --retries=5 \
    CMD php -r 'exit((int) ! @fsockopen("127.0.0.1", 9000, $e, $s, 2));'

ENTRYPOINT ["entrypoint.sh"]
CMD ["php-fpm"]

# ----------------------------------------------------------------------------
# 4. Web server (nginx) — built from the same context, target: web
#    Ships Laravel's public/ (index.php + Vite build) as static files.
#    Uploads are served through the public/storage symlink; the storage
#    volume is mounted at runtime by docker-compose.
# ----------------------------------------------------------------------------
FROM nginx:1.27-alpine AS web

WORKDIR /var/www/html

COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY --from=app /var/www/html/public ./public

EXPOSE 80

HEALTHCHECK --interval=30s --timeout=5s --start-period=10s --retries=3 \
    CMD wget -qO- http://127.0.0.1/ >/dev/null 2>&1 || exit 1

