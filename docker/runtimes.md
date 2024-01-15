# Pixelfed Docker container runtimes

The Pixelfed Dockerfile support multiple target *runtimes* ([Apache](#apache), [Nginx + FPM](#nginx), and [fpm](#fpm)).

You can consider a *runtime* target as individual Dockerfiles, but instead, all of them are build from the same optimized Dockerfile, sharing +90% of their configuration and packages.

**If you are unsure of which runtime to choose, please use the [Apache runtime](#apache) it's the most straightforward one and also the default**

## Apache

Building a custom Pixelfed Docker image using Apache + mod_php can be achieved the following way.

### docker build (Apache)

```shell
docker build \
 -f contrib/docker/Dockerfile \
 --target apache-runtime \
 --tag <docker hub user>/<docker hub repo> \
 .
```

### docker compose (Apache)

```yaml
version: "3"

services:
  app:
    build:
      context: .
      dockerfile: contrib/docker/Dockerfile
      target: apache-runtime
```

## Nginx

Building a custom Pixelfed Docker image using nginx + FPM can be achieved the following way.

### docker build (nginx)

```shell
docker build \
 -f contrib/docker/Dockerfile \
 --target nginx-runtime \
 --build-arg 'PHP_BASE_TYPE=fpm' \
 --tag <docker hub user>/<docker hub repo> \
 .
```

### docker compose (nginx)

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

## FPM

Building a custom Pixelfed Docker image using FPM (only) can be achieved the following way.

### docker build (fpm)

```shell
docker build \
 -f contrib/docker/Dockerfile \
 --target fpm-runtime \
 --build-arg 'PHP_BASE_TYPE=fpm' \
 --tag <docker hub user>/<docker hub repo> \
 .
```

### docker compose (fpm)

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
