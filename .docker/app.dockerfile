#FROM php:8.0-fpm-alpine
FROM php@sha256:9fcb0f5a47540f39f8a0755908498bd9950d818bdceea05669ed9a2060be3790
MAINTAINER Martin Zurowietz <martin@cebitec.uni-bielefeld.de>
LABEL org.opencontainers.image.source https://github.com/biigle/core

RUN ln -s "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
ADD ".docker/all-php.ini" "$PHP_INI_DIR/conf.d/all.ini"
ADD ".docker/app-php.ini" "$PHP_INI_DIR/conf.d/app.ini"

RUN apk add --no-cache \
        openssl \
        postgresql \
        libxml2 \
        libzip \
    && apk add --no-cache --virtual .build-deps \
        postgresql-dev \
        libxml2-dev \
        libzip-dev \
    && docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_pgsql \
        pgsql \
        zip \
        exif \
        soap \
    && apk del --purge .build-deps

RUN apk add --no-cache yaml \
    && apk add --no-cache --virtual .build-deps g++ make autoconf yaml-dev \
    && pecl install yaml \
    && docker-php-ext-enable yaml \
    && apk del --purge .build-deps

ARG PHPREDIS_VERSION=5.3.2
RUN curl -L -o /tmp/redis.tar.gz https://github.com/phpredis/phpredis/archive/${PHPREDIS_VERSION}.tar.gz \
    && tar -xzf /tmp/redis.tar.gz \
    && rm /tmp/redis.tar.gz \
    && mkdir -p /usr/src/php/ext \
    && mv phpredis-${PHPREDIS_VERSION} /usr/src/php/ext/redis \
    && docker-php-ext-install -j$(nproc) redis

RUN apk add --no-cache ffmpeg

COPY composer.lock composer.json /var/www/

COPY database /var/www/database

WORKDIR /var/www

ENV COMPOSER_NO_INTERACTION 1
ENV COMPOSER_ALLOW_SUPERUSER 1
# Install Composer based on the trusted commit:
# https://github.com/composer/getcomposer.org/commit/ce25411cc528444e8c3c60775bde77e01921a1ef
# Ignore platform reqs because the app image is stripped down to the essentials
# and doens't meet some of the requirements.
RUN curl https://raw.githubusercontent.com/composer/getcomposer.org/ce25411cc528444e8c3c60775bde77e01921a1ef/web/installer | php -- \
    && php composer.phar install --no-dev --no-scripts --ignore-platform-reqs \
    && rm -r ~/.composer

COPY . /var/www

# This is required so the artisan optimize command does not fail.
RUN mkdir -p /var/www/storage/framework/views

RUN php composer.phar dump-autoload -o \
    && rm composer.phar

ARG BIIGLE_VERSION
ENV BIIGLE_VERSION=${BIIGLE_VERSION}
