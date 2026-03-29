# Multi-stage production Dockerfile for Laravel 12 with Octane (RoadRunner)

# --- Stage 1: Build PHP dependencies and extensions ---
FROM php:8.4-cli AS builder

# Install system dependencies
RUN apt-get update && apt-get install -y --no-install-recommends \
    libpq-dev \
    libzip-dev \
    libicu-dev \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    unzip \
    && rm -rf /var/lib/apt/lists/*

# Install and configure PHP extensions required by FunLynk
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql \
    && docker-php-ext-install pdo_pgsql zip pcntl sockets bcmath intl gd \
    && pecl install redis \
    && docker-php-ext-enable redis opcache

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY composer.json composer.lock ./

# Install minimal production dependencies
RUN composer install --no-dev --no-interaction --no-scripts --no-progress --prefer-dist

# --- Stage 2: Compile Node.js frontend assets ---
FROM node:20-alpine AS node-builder
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY . .
RUN npm run build

# --- Stage 3: Final Production Image ---
FROM php:8.4-cli

# Install essential runtime libraries
RUN apt-get update && apt-get install -y --no-install-recommends \
    libpq-dev \
    libzip-dev \
    libicu-dev \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    unzip \
    && rm -rf /var/lib/apt/lists/*

# Copy compiled extensions from builder
COPY --from=builder /usr/local/lib/php/extensions /usr/local/lib/php/extensions
COPY --from=builder /usr/local/etc/php/conf.d /usr/local/etc/php/conf.d

# Optimize OPcache for production speeds
RUN echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini \
    && echo "opcache.memory_consumption=256" >> /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini \
    && echo "opcache.interned_strings_buffer=16" >> /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini \
    && echo "opcache.max_accelerated_files=10000" >> /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini \
    && echo "opcache.validate_timestamps=0" >> /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini

# Install RoadRunner (Octane server binary)
COPY --from=ghcr.io/roadrunner-server/roadrunner:2024.1.1 /usr/bin/rr /usr/local/bin/rr

WORKDIR /app

# Copy the entire app and vendor directory from builder
COPY --from=builder /app /app
COPY . .

# Copy Vite compiled assets
COPY --from=node-builder /app/public/build /app/public/build

# Finalize Composer (autoloader optimization)
COPY --from=builder /usr/bin/composer /usr/bin/composer
RUN composer dump-autoload --optimize --classmap-authoritative

# Ensure proper permissions run under 'www-data' instead of root
RUN chown -R www-data:www-data /app \
    && chmod -R 775 /app/storage /app/bootstrap/cache

# Switch to standard unprivileged user
USER www-data

# Default command: run Laravel Octane via RoadRunner
CMD ["php", "artisan", "octane:start", "--server=roadrunner", "--host=0.0.0.0", "--rpc-port=6001", "--port=8000"]
