# Pixelfed Docker FAQ

## How do I use my own Proxy server?

No problem! All you have to do is:

1. Change the `DOCKER_PROXY_PROFILE` key/value pair in your `.env` file to `"disabled"`.
    * This disables the `proxy` *and* `proxy-acme` services in `docker-compose.yml`.
    * The setting is near the bottom of the file.
1. Point your proxy upstream to the exposed `web` port (**Default**: `8080`).
    * The port is controlled by the `DOCKER_WEB_PORT_EXTERNAL_HTTP` key in `.env`.
    * The setting is near the bottom of the file.

## How do I use my own SSL certificate?

No problem! All you have to do is:

1. Change the `DOCKER_PROXY_ACME_PROFILE` key/value pair in your `.env` file to `"disabled"`.
    * This disabled the `proxy-acme` service in `docker-compose.yml`.
    * It does *not* disable the `proxy` service.
1. Put your certificates in `${DOCKER_CONFIG_ROOT}/proxy/certs` (e.g. `./docker-compose/config/proxy/certs`)
    * You may need to create this folder manually if it does not exists.
    * The following files are expected to exist in the directory for the proxy to detect and use them automatically (this is the same directory and file names as LetsEncrypt uses)
        1. `${APP_DOMAIN}.cert.pem`
        1. `${APP_DOMAIN}.chain.pem`
        1. `${APP_DOMAIN}.fullchain.pem`
        1. `${APP_DOMAIN}.key.pem`
    * See the [`nginx-proxy` configuration file for name patterns](https://github.com/nginx-proxy/nginx-proxy/blob/main/nginx.tmpl#L659-L670)

## How do I change the container name prefix?

Change the `DOCKER_CONTAINER_NAME_PREFIX` key/value pair in your `.env` file.
