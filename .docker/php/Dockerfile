FROM dunglas/frankenphp:php8.4-alpine AS base

RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    libzip-dev \
    zip \
    unzip \
    postgresql-dev \
    icu-dev \
    oniguruma-dev \
    linux-headers

RUN docker-php-ext-install \
    pdo \
    pdo_pgsql \
    pgsql \
    mbstring \
    zip \
    intl \
    opcache \
    bcmath

RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .build-deps

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

FROM base AS development

ARG UID=1000
ARG GID=1000

RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && apk del .build-deps

COPY .docker/php/config/dev.ini /usr/local/etc/php/conf.d/

RUN deluser --remove-home www-data \
    && addgroup -g ${GID} -S www-data \
    && adduser -u ${UID} -S -G www-data -h /home/www-data www-data \
    && chown -R www-data:www-data /var/www

USER www-data

EXPOSE 8080
EXPOSE 443
EXPOSE 443/udp

CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]

FROM base AS production

COPY .docker/php/config/prod.ini /usr/local/etc/php/conf.d/

COPY composer.json composer.lock ./

RUN composer install --no-dev --optimize-autoloader --no-interaction --no-progress --no-scripts

RUN chown -R www-data:www-data /var/www \
    && chmod -R 755 /var/www

RUN composer dump-autoload --optimize --classmap-authoritative

USER www-data

EXPOSE 8080
EXPOSE 443
EXPOSE 443/udp

CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]
