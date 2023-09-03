FROM webdevops/php-nginx:8.2 as builder
WORKDIR /app
COPY . ./
COPY contrib/docker/10-*.conf /opt/docker/etc/nginx/vhost.common.d/
COPY contrib/docker/99-init-pixelfed.sh /opt/docker/provision/entrypoint.d/99-init-pixelfed.sh
COPY contrib/docker/artisan*.conf /opt/docker/etc/supervisor.d/

RUN composer update && \
    composer install --no-ansi --no-interaction --no-progress --no-scripts --optimize-autoloader && \
    composer clear-cache && \
    php artisan horizon:install && \
    php artisan horizon:publish && \
    gpasswd -a application tty && \
    mv /app/public/vendor /app/storage/app/public/ && \
    cp -r storage storage.skel && \
    chmod -R ugo+rwX storage.skel && \
    chown -R 1000:1000 storage storage.skel && \
    ln -s /app/storage/.env /app/.env && \
    ln -s /app/storage/app/public /app/public/storage && \
    ln -s /app/storage/app/public/vendor /app/public/vendor

EXPOSE 80 443 9000
