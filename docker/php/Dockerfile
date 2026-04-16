FROM php:8.4-fpm-alpine

RUN apk add --no-cache \
    git \
    curl \
    zip \
    unzip \
    icu-libs \
    libzip \
    libmemcached-libs \
    && apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS \
        icu-dev \
        oniguruma-dev \
        libzip-dev \
        libmemcached-dev \
        zlib-dev \
    && docker-php-ext-install \
        intl \
        mbstring \
        pdo_mysql \
        zip \
        opcache \
    && pecl install memcached \
    && docker-php-ext-enable memcached \
    && apk del .build-deps

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY docker/php/php.ini /usr/local/etc/php/conf.d/custom.ini
