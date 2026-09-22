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

# libvips builder — compile from source for a current release with full AVIF/HEIC support.
FROM serversideup/php:8.5-frankenphp AS vips

# libvips version to compile, change with [--build-arg VIPS_VERSION="8.18.6"]
ARG VIPS_VERSION=8.18.6
ARG VIPS_URL=https://github.com/libvips/libvips/releases/download
ARG VIPS_SHA256=3c41e1d5458081bfa4a5bc54e116c46259c75c6760a18027764555632b9dda3e

USER root
SHELL ["/bin/bash", "-o", "pipefail", "-o", "errexit", "-c"]

RUN apt-get update && apt-get install -y --no-install-recommends \
    build-essential \
    ca-certificates \
    meson \
    ninja-build \
    pkg-config \
    wget \
    xz-utils \
    libglib2.0-dev \
    libexpat1-dev \
    libjpeg-dev \
    libpng-dev \
    libwebp-dev \
    libexif-dev \
    liblcms2-dev \
    libheif-dev \
    libaom-dev \
    libdav1d-dev \
    libjxl-dev \
    liborc-0.4-dev \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /usr/local/vips/src
RUN wget -q "${VIPS_URL}/v${VIPS_VERSION}/vips-${VIPS_VERSION}.tar.xz" \
    && echo "${VIPS_SHA256}  vips-${VIPS_VERSION}.tar.xz" | sha256sum -c - \
    && tar xf "vips-${VIPS_VERSION}.tar.xz"

WORKDIR /usr/local/vips/src/vips-${VIPS_VERSION}
# Pixelfed only handles common web formats (jpeg/png/gif/webp) plus modern avif/heic and jpeg-xl. 
# We enable exactly those delegates and explicitly disable every other loader.
#   - gif     : load uses libvips' bundled libnsgif, save uses bundled cgif, so no giflib dev package is required.
#   - heif    : AVIF/HEIC read+write via distro libheif -> aom/dav1d.
#   - jpeg-xl : JXL read+write via distro libjxl.
#   -Ddebug   : off, and we strip for a lean runtime library.
RUN meson setup build \
        --prefix=/usr/local/vips \
        --libdir=lib \
        --buildtype=release \
        -Ddeprecated=false \
        -Dexamples=false \
        -Dcplusplus=false \
        -Djpeg=enabled \
        -Dpng=enabled \
        -Dwebp=enabled \
        -Dheif=enabled \
        -Djpeg-xl=enabled \
        -Dlcms=enabled \
        -Dexif=enabled \
        -Dtiff=disabled \
        -Dopenjpeg=disabled \
        -Dpdfium=disabled \
        -Dpoppler=disabled \
        -Drsvg=disabled \
        -Dopenexr=disabled \
        -Dopenslide=disabled \
        -Dmatio=disabled \
        -Dnifti=disabled \
        -Dcfitsio=disabled \
        -Dmagick=disabled \
        -Draw=disabled \
        -Duhdr=disabled \
        -Dfftw=disabled \
        -Dfontconfig=disabled \
        -Dpangocairo=disabled \
        -Darchive=disabled \
        -Dppm=false \
        -Danalyze=false \
        -Dradiance=false \
    && meson compile -C build \
    && meson install -C build \
    && strip --strip-unneeded /usr/local/vips/lib/libvips.so.* || true

# Smoke Test
RUN echo "/usr/local/vips/lib" > /etc/ld.so.conf.d/vips.conf && ldconfig \
    && /usr/local/vips/bin/vips --vips-version \
    && /usr/local/vips/bin/vips list | grep -i heif \
    && /usr/local/vips/bin/vips list | grep -i jxl

# PHP base image — FrankenPHP (includes Caddy built-in)
FROM serversideup/php:8.5-frankenphp

ARG RUNTIME_UID=33
ARG RUNTIME_GID=33

WORKDIR /var/www/html

USER root

RUN docker-php-serversideup-set-id www-data ${RUNTIME_UID}:${RUNTIME_GID} && \
    docker-php-serversideup-set-file-permissions --owner ${RUNTIME_UID}:${RUNTIME_GID} --service frankenphp --dir /var/www/html

RUN apt-get update && apt-get install -y \
    unzip \
    zip \
    jpegoptim \
    optipng \
    pngquant \
    gifsicle \
    git \
    curl \
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
    libglib2.0-0t64 \
    libexpat1 \
    libjpeg62-turbo \
    libpng16-16t64 \
    libexif12 \
    liblcms2-2 \
    liborc-0.4-0t64 \
    libheif1 \
    libaom3 \
    libdav1d7 \
    libjxl0.11 \
    libhwy1t64 \
    && rm -rf /var/lib/apt/lists/*

# Bring in the libvips we compiled (shared lib + headers + pkg-config + tools),
# then refresh the linker cache so the PHP vips extension links against it.
COPY --from=vips /usr/local/vips /usr/local/vips
RUN echo "/usr/local/vips/lib" > /etc/ld.so.conf.d/vips.conf && ldconfig

ENV PKG_CONFIG_PATH=/usr/local/vips/lib/pkgconfig \
    PATH=/usr/local/vips/bin:$PATH

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
    ffi

# Pixelfed talks to libvips through jcupitt/vips (via intervention/image-driver-vips) which is an FFI binding.
RUN tee /usr/local/etc/php/conf.d/zz-pixelfed.ini > /dev/null <<'EOF'
ffi.enable=true
EOF

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

# Smoke check 2
RUN php -r '\
        require "vendor/autoload.php"; \
        $im = Jcupitt\Vips\Image::black(16, 16); \
        $im->writeToBuffer(".avif"); \
        $im->writeToBuffer(".jxl"); \
        echo "php-vips FFI OK: libvips " . Jcupitt\Vips\Config::version() . "\n"; \
    '

USER www-data

EXPOSE 8080
EXPOSE 8443
