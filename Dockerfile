# syntax=docker/dockerfile:1.7

# serversideup/php has PHP 8.4 + Composer + every Laravel-relevant extension
# (pdo_mysql, redis, bcmath, opcache, mbstring, intl, gd, zip, sqlite3, …)
# already compiled. Skips the slow `docker-php-ext-install` step entirely.
FROM serversideup/php:8.4-cli-alpine

USER root
RUN apk add --no-cache nodejs npm

WORKDIR /var/www/html

# Install PHP deps first (cached layer until composer.lock changes).
COPY composer.json composer.lock ./
RUN --mount=type=cache,target=/tmp/composer-cache \
    COMPOSER_CACHE_DIR=/tmp/composer-cache \
    composer install --no-dev --no-scripts --no-autoloader --prefer-dist

# Install Node deps (cached layer until package-lock.json changes).
COPY package.json package-lock.json ./
RUN --mount=type=cache,target=/root/.npm npm ci

# Now bring in the rest of the source and finish the build in one layer.
COPY . .
RUN npm run build \
    && composer dump-autoload --optimize --classmap-authoritative \
    && rm -rf node_modules \
    && cp .env.example .env \
    && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views \
                storage/logs storage/app/public bootstrap/cache \
    && php artisan key:generate --force \
    && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 8000
USER www-data
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
