FROM php:8.4.13-alpine

ENV COMPOSER_HOME="/tmp/composer"

COPY --from=composer:2.8.5 /usr/bin/composer /usr/bin/composer

RUN set -x \
    && apk add --no-cache git libpng-dev \
    && docker-php-ext-install gd \
    && mkdir --parents --mode=777 /src ${COMPOSER_HOME}/cache/repo ${COMPOSER_HOME}/cache/files \
    && ln -s /usr/bin/composer /usr/bin/c

WORKDIR /src
