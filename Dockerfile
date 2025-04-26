FROM dunglas/frankenphp:latest AS base

ENV SERVER_NAME=:80

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

RUN install-php-extensions \
    pdo_mysql \
    gd \
    intl \
    zip \
    opcache

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

FROM base AS dev

WORKDIR /app

COPY . .

COPY composer.json composer.lock ./
RUN composer install --no-scripts --no-autoloader --no-dev

# Generate optimized autoloader and run other composer scripts
RUN composer dump-autoload --optimize --no-dev \
    && composer run-script post-autoload-dump --no-dev

# Copy .env.example to .env
COPY .env.example .env

# Set proper permissions
RUN chown -R www-data:www-data /app \
    && chmod -R 755 /app/storage /app/bootstrap/cache

# Run Laravel optimizations
RUN php artisan key:generate \
    && php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache
