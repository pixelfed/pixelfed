# PixelFed: Federated Image Sharing

PixelFed is a federated social image sharing platform, similar to Instagram.
Federation is done using the [ActivityPub](https://activitypub.rocks/) protocol,
which is used by [Mastodon](http://joinmastodon.org/), [PeerTube](https://joinpeertube.org/en/),
[Pleroma](https://pleroma.social/), and more. Through ActivityPub PixelFed can share
and interact with these platforms, as well as other instances of PixelFed. 

**_Please note this is alpha software, not recommended for production use,
and federation is not supported yet._**

PixelFed is very early into the development stage. If you would like to have a
permanent instance with minimal breakage, **do not use this software until
there is a stable release**. The following setup instructions are intended for
testing and development.

## Requirements
 - PHP >= 7.1.3 (7.2+ recommended for stable version)
 - MySQL >= 5.7, Postgres (MariaDB and sqlite are not supported yet)
 - Redis
 - Composer
 - GD or ImageMagick
 - OpenSSL PHP Extension
 - PDO PHP Extension
 - Mbstring PHP Extension
 - Tokenizer PHP Extension
 - XML PHP Extension
 - Ctype PHP Extension
 - JSON PHP Extension
 - JpegOptim
 - Optipng
 - Pngquant 2
 - SVGO
 - Gifsicle

## Installation

This guide assumes you have NGINX/Apache installed, along with the dependencies.
Those will not be covered in these early docs.

```bash
git clone https://github.com/dansup/pixelfed.git
cd pixelfed
composer install
cp .env.example .env
```

**Edit .env file with proper values**

```bash
php artisan key:generate
```

```bash
php artisan storage:link
php artisan migrate
php artisan horizon
```



## Communication

The ways you can communicate on the project are below. Before interacting, please
read through the [Code Of Conduct](CODE_OF_CONDUCT.md).

* IRC: #pixelfed on irc.freenode.net ([#freenode_#pixelfed:matrix.org through
Matrix](https://matrix.to/#/#freenode_#pixelfed:matrix.org)
* Project on Mastodon: [@pixelfed@mastodon.social](https://mastodon.social/@pixelfed)
* E-mail: [hello@pixelfed.org](mailto:hello@pixelfed.org)

## Support

The lead maintainer is on Patreon! You can become a Patron at
https://www.patreon.com/dansup
