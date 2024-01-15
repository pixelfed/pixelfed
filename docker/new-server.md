# New Pixelfed + Docker + Docker Compose server

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
