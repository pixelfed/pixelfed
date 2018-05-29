FROM php:7.2.6-fpm-alpine

ARG COMPOSER_VERSION="1.6.5"
ARG COMPOSER_CHECKSUM="67bebe9df9866a795078bb2cf21798d8b0214f2e0b2fd81f2e907a8ef0be3434"

RUN apk add --no-cache --virtual .build build-base autoconf imagemagick-dev libtool && \
  apk --no-cache add imagemagick git && \
  docker-php-ext-install pdo_mysql pcntl && \
  pecl install imagick && \
  docker-php-ext-enable imagick pcntl imagick && \
  curl -LsS https://getcomposer.org/download/${COMPOSER_VERSION}/composer.phar -o /tmp/composer.phar && \
  echo "${COMPOSER_CHECKSUM}  /tmp/composer.phar" | sha256sum -c - && \
  install -m0755 -o root -g root /tmp/composer.phar /usr/bin/composer.phar && \
  ln -sf /usr/bin/composer.phar /usr/bin/composer && \
  mkdir -p /var/www && \
  install -d -m0755 -o www-data -g www-data /var/www/html/pixelfed \
    /var/www/html/pixelfed/storage \
    /var/www/html/pixelfed/storage/framework \
    /var/www/html/pixelfed/storage/logs \
    /var/www/html/pixelfed/storage/framework/sessions \
    /var/www/html/pixelfed/storage/framework/views \
    /var/www/html/pixelfed/storage/framework/cache && \
  rm /tmp/composer.phar && \
  apk del --purge .build

COPY --chown=www-data . /var/www/html/pixelfed/

WORKDIR /var/www/html/pixelfed
USER www-data
RUN composer install --prefer-source --no-interaction

VOLUME ["/var/www/html"]
USER root
ENV PATH="~/.composer/vendor/bin:./vendor/bin:${PATH}"
