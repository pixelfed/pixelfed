FROM serversideup/php:8.5-frankenphp AS ffmpeg

# ffmpeg version to compile, change with [--build-arg FFMPEG_VERSION="9.0.1"]
ARG FFMPEG_VERSION=9.0.1
ARG FFMPEG_URL=https://ffmpeg.org/releases

USER root
SHELL ["/bin/bash", "-o", "pipefail", "-o", "errexit", "-c"]

RUN apt-get update && apt-get install -y --no-install-recommends \
    autoconf \
    automake \
    build-essential \
    ca-certificates \
    cmake \
    git \
    libtool \
    meson \
    nasm \
    ninja-build \
    pkg-config \
    wget \
    xz-utils \
    yasm \
    libaom-dev \
    libdav1d-dev \
    libmp3lame-dev \
    libnuma-dev \
    libopus-dev \
    libvorbis-dev \
    libvpx-dev \
    libwebp-dev \
    libx264-dev \
    libx265-dev \
    zlib1g-dev \
    liblzma-dev \
    libbz2-dev \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /usr/local/ffmpeg/src
ADD ${FFMPEG_URL}/ffmpeg-${FFMPEG_VERSION}.tar.xz /usr/local/ffmpeg/src/
RUN tar xf ffmpeg-${FFMPEG_VERSION}.tar.xz

WORKDIR /usr/local/ffmpeg/src/ffmpeg-${FFMPEG_VERSION}

#   --toolchain=hardened : compiler hardening (stack protector, FORTIFY, RELRO)
#                          for a tool that parses untrusted user-uploaded media.
#   --enable-lto         : link-time optimization for a small runtime speedup.
RUN ./configure \
    --prefix=/usr/local/ffmpeg \
    --toolchain=hardened \
    --enable-lto \
    --disable-debug \
    --disable-doc \
    --disable-ffplay \
    --disable-static \
    --enable-shared \
    --enable-ffmpeg \
    --enable-ffprobe \
    --enable-gpl \
    --enable-version3 \
    --enable-pthreads \
    --enable-libaom \
    --enable-libdav1d \
    --enable-libmp3lame \
    --enable-libopus \
    --enable-libvorbis \
    --enable-libvpx \
    --enable-libwebp \
    --enable-libx264 \
    --enable-libx265 \
    --enable-zlib \
    --enable-lzma \
    ; \
    make -j"$(nproc)"; \
    make install

# PHP base image — FrankenPHP (includes Caddy built-in)
FROM serversideup/php:8.5-frankenphp

WORKDIR /var/www/html

USER root

RUN apt-get update && apt-get install -y \
    unzip \
    zip \
    jpegoptim \
    optipng \
    pngquant \
    gifsicle \
    libvips42 \
    git \
    curl \
    libaom-dev \
    libdav1d-dev \
    libmp3lame0 \
    libnuma1 \
    libopus0 \
    libvorbis0a \
    libvorbisenc2 \
    libvpx-dev \
    libwebp7 \
    libwebpmux3 \
    libx264-dev \
    libx265-dev \
    && rm -rf /var/lib/apt/lists/*

RUN install-php-extensions \
    bcmath \
    curl \
    exif \
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

COPY --from=ffmpeg /usr/local/ffmpeg/bin/ffmpeg /usr/bin/ffmpeg
COPY --from=ffmpeg /usr/local/ffmpeg/bin/ffprobe /usr/bin/ffprobe
COPY --from=ffmpeg /usr/local/ffmpeg/lib /usr/local/lib

RUN ldconfig \
    && /usr/bin/ffmpeg -version \
    && /usr/bin/ffprobe -version

COPY --chown=www-data:www-data . /var/www/html

RUN chown -R www-data:www-data /var/www/html \
    && find /var/www/html -type f -exec chmod 644 {} \; \
    && find /var/www/html -type d -exec chmod 755 {} \; \
    && chmod -R ug+rwx /var/www/html/storage /var/www/html/bootstrap/cache

RUN composer install --no-ansi --no-interaction --optimize-autoloader

USER www-data

EXPOSE 8080
EXPOSE 8443
