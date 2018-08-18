FROM php:7.2.6-fpm-alpine

ARG COMPOSER_VERSION="1.6.5"
ARG COMPOSER_CHECKSUM="67bebe9df9866a795078bb2cf21798d8b0214f2e0b2fd81f2e907a8ef0be3434"

RUN apk add --no-cache --virtual .build build-base autoconf imagemagick-dev libtool && \
  apk --no-cache add imagemagick git && \
  docker-php-ext-install pdo_mysql pcntl bcmath && \
  pecl install imagick && \
  docker-php-ext-enable imagick pcntl imagick && \
  curl -LsS https://getcomposer.org/download/${COMPOSER_VERSION}/composer.phar -o /tmp/composer.phar && \
  echo "${COMPOSER_CHECKSUM}  /tmp/composer.phar" | sha256sum -c - && \
  install -m0755 -o root -g root /tmp/composer.phar /usr/bin/composer.phar && \
  ln -sf /usr/bin/composer.phar /usr/bin/composer && \
  rm /tmp/composer.phar && \
  apk --no-cache del --purge .build

COPY . /var/www/html/

WORKDIR /var/www/html
RUN install -d -m0755 -o www-data -g www-data \
    /var/www/html/storage \
    /var/www/html/storage/framework \
    /var/www/html/storage/logs \
    /var/www/html/storage/framework/sessions \
    /var/www/html/storage/framework/views \
    /var/www/html/storage/framework/cache && \
  composer install --prefer-source --no-interaction

VOLUME ["/var/www/html"]
ENV PATH="~/.composer/vendor/bin:./vendor/bin:${PATH}"
