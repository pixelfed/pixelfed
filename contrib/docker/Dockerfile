FROM php:7.4-fpm-buster

ARG COMPOSER_VERSION="1.9.2"
ARG COMPOSER_CHECKSUM="58753998712ae435915a452d701ae28a9389653bbf36b3b6adf24e03d90a9467"

RUN apt-get update \
 && apt-get install -y --no-install-recommends apt-utils \
 && apt-get install -y --no-install-recommends git gosu \
      optipng pngquant jpegoptim gifsicle libpq-dev libsqlite3-dev locales zip unzip libzip-dev libcurl4-openssl-dev \
      libfreetype6 libicu-dev libjpeg62-turbo libpng16-16 libxpm4 libwebp6 libmagickwand-6.q16-6 \
      libfreetype6-dev libjpeg62-turbo-dev libpng-dev libxpm-dev libwebp-dev libmagickwand-dev mariadb-client\
 && sed -i '/en_US/s/^#//g' /etc/locale.gen \
 && locale-gen && update-locale \
 && docker-php-source extract \

 && docker-php-ext-install -j$(nproc) pdo_mysql pdo_pgsql bcmath ctype curl iconv intl json mbstring openssl tokenizer xml zip imagick pcntl exif \
 && pecl install imagick \
 && docker-php-ext-enable pcntl imagick exif zip curl \
 
 && curl -LsS https://getcomposer.org/download/${COMPOSER_VERSION}/composer.phar -o /usr/bin/composer \
 && echo "${COMPOSER_CHECKSUM}  /usr/bin/composer" | sha256sum -c - \
 && chmod 755 /usr/bin/composer \
 && apt-get autoremove --purge -y \
       libfreetype6-dev libjpeg62-turbo-dev libpng-dev libxpm-dev libvpx-dev libmagickwand-dev \
 && rm -rf /var/cache/apt \
 && docker-php-source delete

ENV PATH="~/.composer/vendor/bin:./vendor/bin:${PATH}"

COPY . /var/www/

WORKDIR /var/www/
RUN mkdir public.ext && cp -r storage storage.skel \
 && cp contrib/docker/php.ini /usr/local/etc/php/conf.d/pixelfed.ini \
 && composer install --prefer-dist --no-interaction --no-ansi --optimize-autoloader \
 && rm -rf html && ln -s public html

VOLUME /var/www/storage /var/www/bootstrap

CMD cp -r storage.skel/* storage/ \
 && cp -r public/* public.ext/ \
 && chown -R www-data:www-data storage/ \
 && php artisan storage:link \
 && php artisan update \
 && exec php-fpm
