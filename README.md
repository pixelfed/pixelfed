# PixelFed: Federated Image Sharing
[![Backers on Open Collective](https://opencollective.com/pixelfed-528/backers/badge.svg)](#backers)
 [![Sponsors on Open Collective](https://opencollective.com/pixelfed-528/sponsors/badge.svg)](#sponsors) 

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
 - BCMath PHP Extension
 - JpegOptim
 - Optipng
 - Pngquant 2
 - SVGO
 - Gifsicle

## Installation

This guide assumes you have NGINX/Apache installed, along with the dependencies.
Those will not be covered in these early docs.

```bash
git clone https://github.com/pixelfed/pixelfed.git
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

### Contributors

This project exists thanks to all the people who contribute. 
<a href="https://github.com/pixelfed/pixelfed/graphs/contributors"><img src="https://opencollective.com/pixelfed-528/contributors.svg?width=890&button=false" /></a>


### Backers

Thank you to all our backers! 🙏 [[Become a backer](https://opencollective.com/pixelfed-528#backer)]

<a href="https://opencollective.com/pixelfed-528#backers" target="_blank"><img src="https://opencollective.com/pixelfed-528/backers.svg?width=890"></a>


### Sponsors

Support this project by becoming a sponsor. Your logo will show up here with a link to your website. [[Become a sponsor](https://opencollective.com/pixelfed-528#sponsor)]

<a href="https://opencollective.com/pixelfed-528/sponsor/0/website" target="_blank"><img src="https://opencollective.com/pixelfed-528/sponsor/0/avatar.svg"></a>
<a href="https://opencollective.com/pixelfed-528/sponsor/1/website" target="_blank"><img src="https://opencollective.com/pixelfed-528/sponsor/1/avatar.svg"></a>
<a href="https://opencollective.com/pixelfed-528/sponsor/2/website" target="_blank"><img src="https://opencollective.com/pixelfed-528/sponsor/2/avatar.svg"></a>
<a href="https://opencollective.com/pixelfed-528/sponsor/3/website" target="_blank"><img src="https://opencollective.com/pixelfed-528/sponsor/3/avatar.svg"></a>
<a href="https://opencollective.com/pixelfed-528/sponsor/4/website" target="_blank"><img src="https://opencollective.com/pixelfed-528/sponsor/4/avatar.svg"></a>
<a href="https://opencollective.com/pixelfed-528/sponsor/5/website" target="_blank"><img src="https://opencollective.com/pixelfed-528/sponsor/5/avatar.svg"></a>
<a href="https://opencollective.com/pixelfed-528/sponsor/6/website" target="_blank"><img src="https://opencollective.com/pixelfed-528/sponsor/6/avatar.svg"></a>
<a href="https://opencollective.com/pixelfed-528/sponsor/7/website" target="_blank"><img src="https://opencollective.com/pixelfed-528/sponsor/7/avatar.svg"></a>
<a href="https://opencollective.com/pixelfed-528/sponsor/8/website" target="_blank"><img src="https://opencollective.com/pixelfed-528/sponsor/8/avatar.svg"></a>
<a href="https://opencollective.com/pixelfed-528/sponsor/9/website" target="_blank"><img src="https://opencollective.com/pixelfed-528/sponsor/9/avatar.svg"></a>


