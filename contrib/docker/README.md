# Pixelfed Docker images

## Runtimes

The Pixelfed Dockerfile support multiple target *runtimes* ([Apache](#apache), [Nginx + FPM](#nginx), and [fpm](#fpm)).

You can consider a *runtime* target as individual Dockerfiles, but instead, all of them are build from the same optimized Dockerfile, sharing +90% of their configuration and packages.

### Apache

Building a custom Pixelfed Docker image using Apache + mod_php can be achieved the following way.

#### docker build (Apache)

```shell
docker build \
 -f contrib/docker/Dockerfile \
 --target apache-runtime \
 --tag <docker hub user>/<docker hub repo> \
 .
```

#### docker compose (Apache)

```yaml
version: "3"

services:
  app:
    build:
      context: .
      dockerfile: contrib/docker/Dockerfile
      target: apache-runtime
```

### Nginx

Building a custom Pixelfed Docker image using nginx + FPM can be achieved the following way.

#### docker build (nginx)

```shell
docker build \
 -f contrib/docker/Dockerfile \
 --target nginx-runtime \
 --build-arg 'PHP_BASE_TYPE=fpm' \
 --tag <docker hub user>/<docker hub repo> \
 .
```

#### docker compose (nginx)

```yaml
version: "3"

services:
 app:
  build:
   context: .
   dockerfile: contrib/docker/Dockerfile
   target: nginx-runtime
   args:
     PHP_BASE_TYPE: fpm
```

### FPM

Building a custom Pixelfed Docker image using FPM (only) can be achieved the following way.

#### docker build (fpm)

```shell
docker build \
 -f contrib/docker/Dockerfile \
 --target fpm-runtime \
 --build-arg 'PHP_BASE_TYPE=fpm' \
 --tag <docker hub user>/<docker hub repo> \
 .
```

#### docker compose (fpm)

```yaml
version: "3"

services:
 app:
  build:
   context: .
   dockerfile: contrib/docker/Dockerfile
   target: fpm-runtime
   args:
     PHP_BASE_TYPE: fpm
```

## Customizing your `Dockerfile`

### Running commands on container start

#### Description

When a Pixelfed container starts up, the [`ENTRYPOINT`](https://docs.docker.com/engine/reference/builder/#entrypoint) script will

1. Search the `/docker/entrypoint.d/` directory for files and for each file (in lexical order).
1. Check if the file is executable.
    1. If the file is not executable, print an error and exit the container.
1. If the file has the extension `.envsh` the file will be [sourced](https://superuser.com/a/46146).
1. If the file has the extension `.sh` the file will be run like a normal script.
1. Any other file extension will log a warning and will be ignored.

#### Included scripts

* `/docker/entrypoint.d/04-defaults.envsh` calculates Docker container environment variables needed for [templating](#templating) configuration files.
* `/docker/entrypoint.d/05-templating.sh` renders [template](#templating) configuration files.
* `/docker/entrypoint.d/10-storage.sh` ensures Pixelfed storage related permissions and commands are run.
* `/docker/entrypoint.d/20-horizon.sh` ensures [Laravel Horizon](https://laravel.com/docs/master/horizon) used by Pixelfed is configured
* `/docker/entrypoint.d/30-cache.sh` ensures all Pixelfed caches (router, view, config) is warmed

#### Disabling entrypoint or individual scripts

To disable the entire entrypoint you can set the variable `ENTRYPOINT_SKIP=1`.

To disable individual entrypoint scripts you can add the filename to the space (`" "`) separated variable `ENTRYPOINT_SKIP_SCRIPTS`. (example: `ENTRYPOINT_SKIP_SCRIPTS="10-storage.sh 30-cache.sh"`)

### Templating

The Docker container can do some basic templating (more like variable replacement) as part of the entrypoint scripts via [gomplate](https://docs.gomplate.ca/).

Any file put in the `/docker/templates/` directory will be templated and written to the right directory.

#### File path examples

1. To template `/usr/local/etc/php/php.ini` in the container put the source file in `/docker/templates/usr/local/etc/php/php.ini`.
1. To template `/a/fantastic/example.txt` in the container put the source file in `/docker/templates/a/fantastic/example.txt`.
1. To template `/some/path/anywhere` in the container put the source file in `/docker/templates/a/fantastic/example.txt`.

#### Available variables

Variables available for templating are sourced (in order, so *last* source takes precedence) like this:

1. `env:` in your `docker-compose.yml` or `-e` in your `docker run` / `docker compose run`
1. Any exported variables in `.envsh` files loaded *before* `05-templating.sh` (e.g. any file with `04-`, `03-`, `02-`, `01-` or `00-` prefix)
1. All key/value pairs in `/var/www/.env.docker`
1. All key/value pairs in `/var/www/.env`

#### Template guide 101

Please see the [gomplate documentation](https://docs.gomplate.ca/) for a more comprehensive overview.

The most frequent use-case you have is likely to print a environment variable (or a default value if it's missing), so this is how to do that:

* `{{ getenv "VAR_NAME" }}` print an environment variable and **fail** if the variable is not set. ([docs](https://docs.gomplate.ca/functions/env/#envgetenv))
* `{{ getenv "VAR_NAME" "default" }}` print an environment variable and print `default` if the variable is not set. ([docs](https://docs.gomplate.ca/functions/env/#envgetenv))

The script will *fail* if you reference a variable that does not exist (and don't have a default value) in a template.

Please see the

* [gomplate syntax documentation](https://docs.gomplate.ca/syntax/)
* [gomplate functions documentation](https://docs.gomplate.ca/functions/)

## Build settings (arguments)

The Pixelfed Dockerfile utilizes [Docker Multi-stage builds](https://docs.docker.com/build/building/multi-stage/) and [Build arguments](https://docs.docker.com/build/guide/build-args/).

Using *build arguments* allow us to create a flexible and more maintainable Dockerfile, supporting [multiple runtimes](#runtimes) ([FPM](#fpm), [Nginx](#nginx), [Apache + mod_php](#apache)) and end-user flexibility without having to fork or copy the Dockerfile.

*Build arguments* can be configured using `--build-arg 'name=value'` for `docker build`, `docker compose build` and `docker buildx build`. For `docker-compose.yml` the `args` key for [`build`](https://docs.docker.com/compose/compose-file/compose-file-v3/#build) can be used.

### `PHP_VERSION`

The `PHP` version to use when building the runtime container.

Any valid Docker Hub PHP version is acceptable here, as long as it's [published to Docker Hub](https://hub.docker.com/_/php/tags)

**Example values**:

* `8` will use the latest version of PHP 8
* `8.1` will use the latest version of PHP 8.1
* `8.2.14` will use PHP 8.2.14
* `latest` will use whatever is the latest PHP version

**Default value**: `8.1`

### `PHP_PECL_EXTENSIONS`

PECL extensions to install via `pecl install`

Use [PHP_PECL_EXTENSIONS_EXTRA](#php_pecl_extensions_extra) if you want to add *additional* extenstions.

Only change this setting if you want to change the baseline extensions.

See the [`PECL extensions` documentation on Docker Hub](https://hub.docker.com/_/php) for more information.

**Default value**: `imagick redis`

### `PHP_PECL_EXTENSIONS_EXTRA`

Extra PECL extensions (separated by space) to install via `pecl install`

See the [`PECL extensions` documentation on Docker Hub](https://hub.docker.com/_/php) for more information.

**Default value**: `""`

### `PHP_EXTENSIONS`

PHP Extensions to install via `docker-php-ext-install`.

**NOTE:** use [`PHP_EXTENSIONS_EXTRA`](#php_extensions_extra) if you want to add *additional* extensions, only override this if you want to change the baseline extensions.

See the [`How to install more PHP extensions` documentation on Docker Hub](https://hub.docker.com/_/php) for more information

**Default value**: `intl bcmath zip pcntl exif curl gd`

### `PHP_EXTENSIONS_EXTRA`

Extra PHP Extensions (separated by space) to install via `docker-php-ext-install`.

See the [`How to install more PHP extensions` documentation on Docker Hub](https://hub.docker.com/_/php) for more information.

**Default value**: `""`

### `PHP_EXTENSIONS_DATABASE`

PHP database extensions to install.

By default we install both `pgsql` and `mysql` since it's more convinient (and adds very little build time! but can be overwritten here if required.

**Default value**: `pdo_pgsql pdo_mysql pdo_sqlite`

### `COMPOSER_VERSION`

The version of Composer to install.

Please see the [Docker Hub `composer` page](https://hub.docker.com/_/composer) for valid values.

**Default value**: `2.6`

### `APT_PACKAGES_EXTRA`

Extra APT packages (separated by space) that should be installed inside the image by `apt-get install`

**Default value**: `""`

### `NGINX_VERSION`

Version of `nginx` to when targeting [`nginx-runtime`](#nginx).

Please see the [Docker Hub `nginx` page](https://hub.docker.com/_/nginx) for available versions.

**Default value**: `1.25.3`

### `PHP_BASE_TYPE`

The `PHP` base image layer to use when building the runtime container.

When targeting

* [`apache-runtime`](#apache) use `apache`
* [`fpm-runtime`](#fpm) use `fpm`
* [`nginx-runtime`](#nginx) use `fpm`

**Valid values**:

* `apache`
* `fpm`
* `cli`

**Default value**: `apache`

### `PHP_DEBIAN_RELEASE`

The `Debian` Operation System version to use.

**Valid values**:

* `bullseye`
* `bookworm`

**Default value**: `bullseye`
