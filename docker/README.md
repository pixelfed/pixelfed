# Pixelfed + Docker + Docker Compose

This guide will help you install and run Pixelfed on **your** server using [Docker Compose](https://docs.docker.com/compose/).

## Prerequisites

Recommendations and requirements for hardware and software needed to run Pixelfed using Docker Compose.

It's highly recommended that you have *some* experience with Linux (e.g. Ubuntu or Debian), SSH, and lightweight server administration.

### Server

A VPS or dedicated server you can SSH into, for example

* [linode.com VPS](https://www.linode.com/)
* [DigitalOcean VPS](https://digitalocean.com/)
* [Hetzner](https://www.hetzner.com/)

### Hardware

Hardware requirements depends on the amount of users you have (or plan to have), and how active they are.

A safe starter/small instance hardware for 25 users and blow are:

* **CPU/vCPU** `2` cores.
* **RAM** `2-4 GB` as your instance grow, memory requirements will increase for the database.
* **Storage** `20-50 GB` HDD is fine, but ideally SSD or NVMe, *especially* for the database.
* **Network** `100 Mbit/s` or faster.

### Domain and DNS

* A **Domain** (or subdomain) is needed for the Pixelfed server (for example, `pixelfed.social` or `pixelfed.mydomain.com`)
* Having the required `A`/`CNAME` DNS records for your domain (above) pointing to your server.
  * Typically an `A` record for the root (sometimes shown as `@`) record for `mydomain.com`.
  * Possibly an `A` record for `www.` subdomain as well.

### Network

* Port `80` (HTTP) and `443` (HTTPS) ports forwarded to the server.
  * Example for Ubuntu using [`ufw`](https://help.ubuntu.com/community/UFW) for port `80`: `ufw allow 80`
  * Example for Ubuntu using [`ufw`](https://help.ubuntu.com/community/UFW) for port `443`: `ufw allow 443`

### Optional

* An **Email/SMTP provider** for sending e-mails to your users, such as e-mail confirmation and notifications.
* An **Object Storage** provider for storing all images remotely, rather than locally on your server.

#### E-mail / SMTP provider

**NOTE**: If you don't plan to use en e-mail/SMTP provider, then make sure to set  `ENFORCE_EMAIL_VERIFICATION="false"` in your `.env` file!

There are *many* providers out there, with wildly different pricing structures, features, and reliability.

It's beyond the cope of this document to detail which provider to pick, or how to correctly configure them, but some providers that is known to be working well - with generous free tiers and affordable packages - are included for your convince (*in no particular order*) below:

* [Simple Email Service (SES)](https://aws.amazon.com/ses/) by Amazon Web Services (AWS) is pay-as-you-go with a cost of $0.10/1000 emails.
* [Brevo](https://www.brevo.com/) (formerly SendInBlue) has a Free Tier with 300 emails/day.
* [Postmark](https://postmarkapp.com/) has a Free Tier with 100 emails/month.
* [Forward Email](https://forwardemail.net/en/private-business-email?pricing=true) has a $3/mo/domain plan with both sending and receiving included.
* [Mailtrap](https://mailtrap.io/email-sending/) has a 1000 emails/month free-tier (their `Email Sending` product, *not* the `Email Testing` one).

#### Object Storage

**NOTE**: This is *entirely* optional - by default Pixelfed will store all uploads (videos, images, etc.) directly on your servers storage.

> Object storage is a technology that stores and manages data in an unstructured format called objects. Modern organizations create and analyze large volumes of unstructured data such as photos, videos, email, web pages, sensor data, and audio files
>
> -- [*What is object storage?*](https://aws.amazon.com/what-is/object-storage/) by Amazon Web Services

It's beyond the cope of this document to detail which provider to pick, or how to correctly configure them, but some providers that is known to be working well - with generous free tiers and affordable packages - are included for your convince (*in no particular order*) below:

* [R2](https://www.cloudflare.com/developer-platform/r2/) by CloudFlare has cheap storage, free *egress* (e.g. people downloading images) and included (and free) Content Delivery Network (CDN).
* [B2 cloud storage](https://www.backblaze.com/cloud-storage) by Backblaze.
* [Simple Storage Service (S3)](https://aws.amazon.com/s3/) by Amazon Web Services.

### Software

Required software to be installed on your server

* `git` can be installed with `apt-get install git` on Debian/Ubuntu
* `docker` can be installed by [following the official Docker documentation](https://docs.docker.com/engine/install/)

## Getting things ready

Connect via SSH to your server and decide where you want to install Pixelfed.

In this guide I'm going to assume it will be installed at `/data/pixelfed`.

1. **Install required software** as mentioned in the [Software Prerequisites section above](#software)
1. **Create the parent directory** by running `mkdir -p /data`
1. **Clone the Pixelfed repository** by running `git clone https://github.com/pixelfed/pixelfed.git /data/pixelfed`
1. **Change to the Pixelfed directory** by running `cd /data/pixelfed`

## Modifying your settings (`.env` file)

### Copy the example configuration file

Pixelfed contains a default configuration file (`.env.docker`) you should use as a starter, however, before editing anything, make a copy of it and put it in the *right* place (`.env`).

Run the following command to copy the file: `cp .env.docker .env`

### Modifying the configuration file

The configuration file is *quite* long, but the good news is that you can ignore *most* of it, most of the *server-specific* settings are configured for you out of the box.

The minimum required settings you **must** change is:

* (required) `APP_DOMAIN` which is the hostname you plan to run your Pixelfed server on (e.g. `pixelfed.social`) - must **not** include `http://` or a trailing slash (`/`)!
* (required) `DB_PASSWORD` which is the database password, you can use a service like [pwgen.io](https://pwgen.io/en/) to generate a secure one.
* (optional) `ENFORCE_EMAIL_VERIFICATION` should be set to `"false"` if you don't plan to send emails.
* (optional) `MAIL_DRIVER` and related `MAIL_*` settings if you plan to use an [email/SMTP provider](#e-mail--smtp-provider) - See [Email variables documentation](https://docs.pixelfed.org/running-pixelfed/installation/#email-variables).
* (optional) `PF_ENABLE_CLOUD` / `FILESYSTEM_CLOUD` if you plan to use an [Object Storage provider](#object-storage).

See the [`Configure environment variables`](https://docs.pixelfed.org/running-pixelfed/installation/#app-variables) documentation for details!

You need to mainly focus on following sections

* [App variables](https://docs.pixelfed.org/running-pixelfed/installation/#app-variables)
* [Email variables](https://docs.pixelfed.org/running-pixelfed/installation/#email-variables)

You can skip the following sections, since they are already configured/automated for you:

* `Redis`
* `Database` (except for `DB_PASSWORD`)
* `One-time setup tasks`

### Starting the service

With everything in place and (hopefully) well-configured, we can now go ahead and start our services by running

```shell
docker compose up -d
```

This will download all the required Docker images, start the containers, and being the automatic setup.

You can follow the logs by running `docker compose logs` - you might want to scroll to the top to logs from the start.

You can use the CLI flag `--tail=100` to only see the most recent (`100` in this example) log lines for each container.

You can use the CLI flag `--follow` to continue to see log output from the containers.

You can combine `--tail=100` and `--follow` like this `docker compose logs --tail=100 --follow`.

If you only care about specific contaieners, you can add them to the end of the command like this `docker compose logs web worker proxy`.

## Runtimes

The Pixelfed Dockerfile support multiple target *runtimes* ([Apache](#apache), [Nginx + FPM](#nginx), and [fpm](#fpm)).

You can consider a *runtime* target as individual Dockerfiles, but instead, all of them are build from the same optimized Dockerfile, sharing +90% of their configuration and packages.

**If you are unsure of which runtime to choose, please use the [Apache runtime](#apache) it's the most straightforward one and also the default**

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
    1. If the file is *not* executable, print an error and exit the container.
1. If the file has the extension `.envsh` the file will be [sourced](https://superuser.com/a/46146).
1. If the file has the extension `.sh` the file will be run like a normal script.
1. Any other file extension will log a warning and will be ignored.

#### Debugging

You can set environment variable `ENTRYPOINT_DEBUG=1` to show verbose output of what each `entrypoint.d` script is doing.

You can also `docker exec` or `docker run` into a container and run `/`

#### Included scripts

* `/docker/entrypoint.d/04-defaults.envsh` calculates Docker container environment variables needed for [templating](#templating) configuration files.
* `/docker/entrypoint.d/05-templating.sh` renders [template](#templating) configuration files.
* `/docker/entrypoint.d/10-storage.sh` ensures Pixelfed storage related permissions and commands are run.
* `//docker/entrypoint.d/15-storage-permissions.sh` (optionally) ensures permissions for files are corrected (see [fixing ownership on startup](#fixing-ownership-on-startup))
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

Please see the [`gomplate` documentation](https://docs.gomplate.ca/) for a more comprehensive overview.

The most frequent use-case you have is likely to print a environment variable (or a default value if it's missing), so this is how to do that:

* `{{ getenv "VAR_NAME" }}` print an environment variable and **fail** if the variable is not set. ([docs](https://docs.gomplate.ca/functions/env/#envgetenv))
* `{{ getenv "VAR_NAME" "default" }}` print an environment variable and print `default` if the variable is not set. ([docs](https://docs.gomplate.ca/functions/env/#envgetenv))

The script will *fail* if you reference a variable that does not exist (and don't have a default value) in a template.

Please see the

* [`gomplate` syntax documentation](https://docs.gomplate.ca/syntax/)
* [`gomplate` functions documentation](https://docs.gomplate.ca/functions/)

### Fixing ownership on startup

You can set the environment variable `ENTRYPOINT_ENSURE_OWNERSHIP_PATHS` to a list of paths that should have their `$USER` and `$GROUP` ownership changed to the configured runtime user and group during container bootstrapping.

The variable is a space-delimited list shown below and accepts both relative and absolute paths:

* `ENTRYPOINT_ENSURE_OWNERSHIP_PATHS="./storage ./bootstrap"`
* `ENTRYPOINT_ENSURE_OWNERSHIP_PATHS="/some/other/folder"`

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
