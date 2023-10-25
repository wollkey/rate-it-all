# syntax=docker/dockerfile:1.4
FROM php:8.2-fpm-alpine3.17

RUN  --mount=type=bind,from=mlocati/php-extension-installer:2.1,source=/usr/bin/install-php-extensions,target=/usr/local/bin/install-php-extensions \
     apk update && \
      install-php-extensions opcache zip bcmath sockets mbstring xml ctype iconv intl pdo_pgsql pgsql && \
     apk del --no-cache  ${PHPIZE_DEPS} ${BUILD_DEPENDS}

WORKDIR /var/www

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
COPY ./composer.* .

RUN composer install --no-cache --no-ansi --no-autoloader --no-scripts --no-dev

RUN set -x \
    && composer dump-autoload -n --optimize

CMD ["php-fpm"]
