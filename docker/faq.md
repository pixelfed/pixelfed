# Pixelfed Docker FAQ

## I already have a Proxy, how do I disable the included one?

No problem! All you have to do is

1. *Comment out*  (or delete) the `proxy` and `proxy-acme` services in `docker-compose.yml`
1. *Uncomment* the `ports` block for the `web` servince in `docker-compose.yml`
1. Change the `DOCKER_WEB_PORT_EXTERNAL_HTTP` setting in your `.env` if you want to change the port from the default `8080`
1. Point your proxy upstream to the exposed `web` port.

## I already have a SSL certificate, how do I use it?

1. *Comment out* (or delete) the `proxy-acme` service in `docker-compose.yml`
1. Put your certificates in `${DOCKER_CONFIG_ROOT}/proxy/certs/${APP_DOMAIN}/`. The following files are expected to exist in the directory for the proxy to detect and use them automatically (this is the same directory and file names as LetsEncrypt uses)
    1. `cert.pem`
    1. `chain.pem`
    1. `fullchain.pem`
    1. `key.pem`
