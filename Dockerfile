FROM php:8.2-cli

RUN apt-get update && apt-get install -y \
    git unzip curl libzip-dev pkg-config libssl-dev \
    && docker-php-ext-install zip

# 🧠 Conditional Xdebug install and enable
RUN if ! php -m | grep -q xdebug; then \
        pecl install xdebug && docker-php-ext-enable xdebug; \
    else \
        docker-php-ext-enable xdebug; \
    fi

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set timezone
RUN echo "date.timezone=America/Chicago" > /usr/local/etc/php/conf.d/timezone.ini
RUN echo "xdebug.mode=debug,develop,coverage" > /usr/local/etc/php/conf.d/xdebug-mode.ini