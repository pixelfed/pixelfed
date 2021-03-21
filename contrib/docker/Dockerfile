FROM ismaelmds/pixelfed-base

COPY . /var/www/
# for detail why storage is copied this way, pls refer to https://github.com/pixelfed/pixelfed/pull/2137#discussion_r434468862
RUN cp -r storage storage.skel \
  && composer install --prefer-dist --no-interaction --no-ansi --optimize-autoloader \
  && rm -rf html && ln -s public html \
  && chown -R www-data:www-data /var/www
VOLUME /var/www/storage /var/www/bootstrap

CMD ["/var/www/contrib/docker/start.apache.sh"]
