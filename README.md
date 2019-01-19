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

Tested on a fresh Debian 9 installation

## Requirements (installed during installation)
 - PHP >= 7.1.3 < 7.3 (7.2.x recommended for stable version)
 - MySQL >= 5.7 (Postgres, MariaDB and sqlite are not supported)
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

Install Git + Download Scripts
```sh
$ sudo apt install git
```

Install sury.org package source
```sh
$ sudo apt-get update
$ sudo apt-get -y install apt-transport-https lsb-release ca-certificates
$ sudo wget -O /etc/apt/trusted.gpg.d/php.gpg https://packages.sury.org/php/apt.gpg
$ sudo sh -c 'echo "deb https://packages.sury.org/php/ $(lsb_release -sc) main" > /etc/apt/sources.list.d/php.list'
$ sudo apt-get update
```

Install Erlang Solutions repo
```sh
$ sudo wget https://packages.erlang-solutions.com/erlang-solutions_1.0_all.deb && ${SUDO} dpkg -i erlang-solutions_1.0_all.deb        
$ sudo apt-get update
```

Install Elixir   
```sh
$ sudo apt-get install -y erlang-dev erlang-xmerl erlang-parsetools elixir
```

Install Node.js 10.x
```sh
$ sudo wget -qO- https://deb.nodesource.com/setup_10.x | ${SUDO} -E bash -
$ sudo apt-get install -y nodejs build-essential
```

Install Erlang Solutions repo
```sh
$ sudo wget https://packages.erlang-solutions.com/erlang-solutions_1.0_all.deb && ${SUDO} dpkg -i erlang-solutions_1.0_all.deb        
$ sudo apt-get update
```

Install Elixir   
```sh
$ sudo apt-get install -y erlang-dev erlang-xmerl erlang-parsetools elixir
```

Install Node.js 10.x
```sh
$ sudo wget -qO- https://deb.nodesource.com/setup_10.x | ${SUDO} -E bash -
$ sudo apt-get install -y nodejs build-essential
```

Install Yarn
```sh
$ sudo apt-get update && ${SUDO} apt-get install yarn
```

Install htop, bmon, mc and misc tools required by pixelfed
```sh
$ sudo apt-get install -y htop bmon mc pngquant optipng jpegoptim gifsicle
```

Install PHP 7.2 FPM + additional extensions requiredd by pixelfed
```sh
$ sudo apt-get install -y php7.2-fpm php7.2 php7.2-common php7.2-cli php7.2-gd php7.2-mbstring php7.2-xml php7.2-json php7.2-bcmath php7.2-pgsql php7.2-curl
```

Install Nginx, Redis and Postgres
```sh
$ sudo apt-get install -y nginx redis-server postgresql
```

Configure Redis supervised by systemd
```sh
$ sudo sed -i "s/supervised no/supervised systemd/" /etc/redis/redis.conf
$ sudo systemctl restart redis
```

Install svgo required by pixelfed, also curl
```sh
$ sudo npm install -g svgo
$ sudo apt install curl
```

Install Erlang Solutions repo
```sh
$ sudo wget https://packages.erlang-solutions.com/erlang-solutions_1.0_all.deb && ${SUDO} dpkg -i erlang-solutions_1.0_all.deb        
$ sudo apt-get update
```

Install Elixir   
```sh
$ sudo apt-get install -y erlang-dev erlang-xmerl erlang-parsetools elixir
```

Install Node.js 10.x
```sh
$ sudo wget -qO- https://deb.nodesource.com/setup_10.x | ${SUDO} -E bash -
$ sudo apt-get install -y nodejs build-essential
```

Install Yarn
```sh
$ sudo apt-get update && ${SUDO} apt-get install yarn
```

Install htop, bmon, mc and misc tools required by pixelfed
```sh
$ sudo apt-get install -y htop bmon mc pngquant optipng jpegoptim gifsicle
```

Install PHP 7.2 FPM + additional extensions requiredd by pixelfed
```sh
$ sudo apt-get install -y php7.2-fpm php7.2 php7.2-common php7.2-cli php7.2-gd php7.2-mbstring php7.2-xml php7.2-json php7.2-bcmath php7.2-pgsql php7.2-curl
```

Install Nginx, Redis and Postgres
```sh
$ sudo apt-get install -y nginx redis-server postgresql
```

Configure Redis supervised by systemd
```sh
$ sudo sed -i "s/supervised no/supervised systemd/" /etc/redis/redis.conf
$ sudo systemctl restart redis
```

Install svgo required by pixelfed, also curl
```sh
$ sudo npm install -g svgo
$ sudo apt install curl
```

Install composer
```sh
$ sudo curl -s https://getcomposer.org/installer | php
$ sudo mv composer.phar /usr/local/bin/composer
$ sudo chmod +x /usr/local/bin/composer
```

Restart php7.2-fpm (might not be needed)
```sh
$ sudo systemctl restart php7.2-fpm
```

Create pixelfed vhost directory
```sh
$ sudo mkdir /var/www/vhosts
$ sudo mkdir /var/www/vhosts/pixelfed
```

Install Erlang Solutions repo
```sh
$ sudo wget https://packages.erlang-solutions.com/erlang-solutions_1.0_all.deb && ${SUDO} dpkg -i erlang-solutions_1.0_all.deb        
$ sudo apt-get update
```

Install Elixir   
```sh
$ sudo apt-get install -y erlang-dev erlang-xmerl erlang-parsetools elixir
```

Install Node.js 10.x
```sh
$ sudo wget -qO- https://deb.nodesource.com/setup_10.x | ${SUDO} -E bash -
$ sudo apt-get install -y nodejs build-essential
```

Install Yarn
```sh
$ sudo apt-get update && ${SUDO} apt-get install yarn
```

Install htop, bmon, mc and misc tools required by pixelfed
```sh
$ sudo apt-get install -y htop bmon mc pngquant optipng jpegoptim gifsicle
```

Install PHP 7.2 FPM + additional extensions requiredd by pixelfed
```sh
$ sudo apt-get install -y php7.2-fpm php7.2 php7.2-common php7.2-cli php7.2-gd php7.2-mbstring php7.2-xml php7.2-json php7.2-bcmath php7.2-pgsql php7.2-curl
```

Install Nginx, Redis and Postgres
```sh
$ sudo apt-get install -y nginx redis-server postgresql
```

Configure Redis supervised by systemd
```sh
$ sudo sed -i "s/supervised no/supervised systemd/" /etc/redis/redis.conf
$ sudo systemctl restart redis
```

Install svgo required by pixelfed, also curl
```sh
$ sudo npm install -g svgo
$ sudo apt install curl
```

Install composer
```sh
$ sudo curl -s https://getcomposer.org/installer | php
$ sudo mv composer.phar /usr/local/bin/composer
$ sudo chmod +x /usr/local/bin/composer
```

Restart php7.2-fpm (might not be needed)
```sh
$ sudo systemctl restart php7.2-fpm
```

Create pixelfed vhost directory
```sh
$ sudo mkdir /var/www/vhosts
$ sudo mkdir /var/www/vhosts/pixelfed
```

Install pixelfed into vhost
```sh
$ sudo git clone https://github.com/pixelfed/pixelfed.git /var/www/vhosts/pixelfed
$ sudo mkdir /var/www/vhosts/pixelfed/logs  
$ sudo chown $USERNAME:$GROUP /var/www/vhosts/pixelfed/ -R
$ sudo apt install zip unzip
cd /var/www/vhosts/pixelfed && composer install
cp /var/www/vhosts/pixelfed/.env.example /var/www/vhosts/pixelfed/.env
```

Edit /var/www/vhosts/pixelfed/.env and change:
```sh
APP_URL=http://localhost -> change localhost to your domain name
ADMIN_DOMAIN="localhost" -> change localhost to your domain name
APP_DOMAIN="localhost" -> change localhost to your domain name
```

Continue installing pixelfed into vhost
```sh
cd /var/www/vhosts/pixelfed && php artisan key:generate
cd /var/www/vhosts/pixelfed && php artisan storage:link        
```

Create pixelfed database user and database
```sh
cd /tmp
wget https://dev.mysql.com/get/mysql-apt-config_0.8.11-1_all.deb
$ sudo dpkg -i mysql-apt-config* 
$ sudo apt update 
$ sudo apt-get install mysql-server mysql-client mysql-common php7.2-mysql -y
$ sudo systemctl status mysql 
$ mysql_secure_installation 
$ mysql -u root -p 
mysql> CREATE USER 'pixelfed'@'localhost' IDENTIFIED BY 'password'; 
mysql> CREATE DATABASE pixelfed; 
mysql> GRANT ALL PRIVILEGES ON pixelfed.* TO 'pixelfed'@'localhost'; 
mysql> FLUSH PRIVILEGES;  
mysql> exit
```

Edit .env and change:
```sh
DB_CONNECTION=mysql
DB_DATABASE=pixelfed
DB_username=pixelfed
DB_PASSWORD=password
DB_PORT=3306
```

Migrate DB
```sh
$ cd /var/www/vhosts/pixelfed && php artisan migrate
```

Install screen
```sh
$ sudo apt install screen
```

Execute screen and Horizon
```sh
$ screen
sudo -u www-data php artisan horizon
```

Leave screen on background (ctrl + a + d)
```sh
$ sudo nano /etc/nginx/sites-available/example.com.conf

```

Edit the nginx config file as https://github.com/capmisson/pixelfedinstall/blob/master/example.com.conf

```sh
$ sudo ln -s /etc/nginx/sites-available/example.com.conf /etc/nginx/sites-enabled/
$ sudo service nginx reload
$ sudo nano /etc/apt/sources.list
```

Add Sources for Certbot + Stretch-Backports:
```sh
deb http://deb.debian.org/debian stretch-backports main contrib non-free
deb-src http://deb.debian.org/debian stretch-backports main contrib non-free
```

Then install certbot & stretch-backports:
```sh
$ sudo apt update
$ sudo apt install python-certbot-nginx -t stretch-backports
$ sudo certbot --nginx -d example.com -d www.example.com
```
Select the option of redirecting all transit thru https

```sh
$ sudo nano /var/www/vhosts/pixelfed/.env
```

Edit app_url to add https
Edit smtp config (MailGun recommended) example config for Mailgun:
** Config for Mailgun:

```sh
MAIL_DRIVER=smtp
MAIL_HOST=smtp.mailgun.org
MAIL_PORT=587 
MAIL_USERNAME=postmaster@example.com
MAIL_PASSWORD=YOURMAILGUNSMTPPASSWORD
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="pixelfed@example.com"
MAIL_FROM_NAME="Pixelfed"
```

Then refresh cache as follows:
```sh
$ cd /var/www/vhosts/pixelfed
$ php artisan config:cache
$ sudo chown www-data:www-data /var/www/vhosts/pixelfed/ -R
```

Go to your domain, create a new user and then if you want to upgrade it to admin:
```sh
$ sudo chown username:group /var/www/vhosts/pixelfed/ -R
$ php artisan tinker
$ $username = 'yourusername';
$ $user = User::whereUsername($username)->first();
$ $user->is_admin = true;
$ $user->save();
$ exit
$ php artisan config:cache
$ sudo chown www-data:www-data /var/www/vhosts/pixelfed/ -R
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


