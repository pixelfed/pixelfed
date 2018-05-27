FROM php:7.2-fpm-alpine

RUN apk add --no-cache git imagemagick \
    && apk add --no-cache --virtual .build build-base autoconf imagemagick-dev libtool \
    && docker-php-ext-install pdo_mysql pcntl \
    && pecl install imagick \
    && docker-php-ext-enable imagick \
    && apk del --purge .build

RUN curl -sS https://getcomposer.org/installer | php \
    && mv composer.phar /usr/local/bin/ \
    && ln -s /usr/local/bin/composer.phar /usr/local/bin/composer

WORKDIR /var/www/html
COPY . .
RUN composer install --prefer-source --no-interaction
ENV PATH="~/.composer/vendor/bin:./vendor/bin:${PATH}"
