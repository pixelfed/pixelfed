FROM serversideup/php:8.4-fpm-nginx

# Set working directory
WORKDIR /var/www/html

# Switch to root to install packages
USER root

# Install system dependencies and PHP extensions
RUN apt-get update && apt-get install -y \
    ffmpeg \
    unzip \
    zip \
    jpegoptim \
    optipng \
    pngquant \
    gifsicle \
    libvips42 \
    git \
    curl \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions using the built-in helper
RUN install-php-extensions \
    bcmath \
    curl \
    gd \
    imagick \
    intl \
    mbstring \
    xml \
    zip \
    pdo_mysql \
    redis \
    vips \
    ffi

# Copy application files
COPY --chown=www-data:www-data . /var/www/html

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html \
    && find /var/www/html -type f -exec chmod 644 {} \; \
    && find /var/www/html -type d -exec chmod 755 {} \; \
    && chmod -R ug+rwx /var/www/html/storage /var/www/html/bootstrap/cache

# Install composer dependencies
RUN composer install --no-ansi --no-interaction --optimize-autoloader

# Switch back to www-data user
USER www-data

# Expose port 8080 (default for serversideup/php)
EXPOSE 8080
