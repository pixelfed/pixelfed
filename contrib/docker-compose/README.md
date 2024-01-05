# Pixelfed + Docker + Docker Compose

## Prerequisites

* One of the `docker-compose.yml` files in this directory
* A copy of the `example.env` file named `.env` next to `docker-compose.yml`

Your folder should look like this

```plain
.
├── .env
└── docker-compose.yml
```

## Modifying your settings (`.env` file)

Minimum required settings to change is:

* `APP_NAME`
* `APP_DOMAIN`
* `DB_PASSWORD`

See the [`Configure environment variables`](https://docs.pixelfed.org/running-pixelfed/installation/#app-variables) documentation for details!

You need to mainly focus on following sections

* [App variables](https://docs.pixelfed.org/running-pixelfed/installation/#app-variables)
* [Email variables](https://docs.pixelfed.org/running-pixelfed/installation/#email-variables)

Since the following things are configured for you out of the box:

* `Redis`
* `Database` (except for `DB_PASSWORD`)
