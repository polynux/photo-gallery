FROM dunglas/frankenphp:latest AS base

ENV SERVER_NAME=:80
ENV CI=true

ARG UID=1000
ARG GID=1000
ENV USER=appuser

COPY "./php.ini-production" "/usr/local/etc/php/php.ini"

RUN install-php-extensions \
    pdo_mysql \
    gd \
    intl \
    zip \
    opcache

RUN \
	useradd ${USER}; \
    usermod -u ${UID} ${USER}; \
    groupmod -g ${GID} ${USER}; \
	setcap CAP_NET_BIND_SERVICE=+eip /usr/local/bin/frankenphp; \
	chown -R ${UID}:${GID} /config/caddy /data/caddy

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


# Build frontend assets
FROM node:20-slim AS frontend
ENV PNPM_HOME="/pnpm"
ENV PATH="$PNPM_HOME:$PATH"
RUN corepack enable
COPY . /app
RUN rm -rf /app/node_modules /app/public/build
WORKDIR /app

RUN --mount=type=cache,id=pnpm,target=/pnpm/store pnpm install
RUN pnpm run build

FROM dev AS prod
WORKDIR /app
COPY --from=frontend /app/public/build /app/public/build

# Set proper permissions
RUN chown -R ${UID}:${GID} /app \
    && chmod -R 755 /app/storage /app/bootstrap/cache

USER ${USER}

# Run Laravel optimizations
RUN php artisan key:generate \
    && php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache

RUN php artisan storage:link \
    && php artisan optimize:clear
