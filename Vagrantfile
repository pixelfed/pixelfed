
# Thank You StackOverflow!
# http://stackoverflow.com/questions/26811089/vagrant-how-to-have-host-platform-specific-provisional-step
module OS
    def OS.windows?
        (/cygwin|mswin|mingw|bccwin|wince|emx/ =~ RUBY_PLATFORM) != nil
    end

    def OS.mac?
        (/darwin/ =~ RUBY_PLATFORM) != nil
    end

    def OS.unix?
        !OS.windows?
    end

    def OS.linux?
        OS.unix? and not OS.mac?
    end
end


Vagrant.configure("2") do |config|
  config.vm.box = "ubuntu/bionic64"

  #if OS.unix?
  #  config.vm.synced_folder ".", "/vagrant", type: "nfs"
  #end

  config.vm.define "pixelfed"
  config.vm.hostname = "pixelfed.local"


  config.vm.provision :shell do |shell|
      shell.inline = "
                     apt-get update && apt-get -y upgrade && \
                     apt-get -y install php7.2-fpm php7.2-gd php7.2-mbstring php-imagick  \
                     php7.2-mysql mysql-server-5.7 redis-server \
                     graphicsmagick php-json php-services-json jpegoptim nginx-full \
                     optipng pngquant gifsicle composer && \
                     cd /vagrant && \
                     su vagrant -c 'composer install' && cp .env.example .env && \
                     mysql -e 'create database homestead' && \
                     mysql -e \"grant all on homestead.* to 'homestead'@'localhost' identified by 'secret'\" && \
                     cp /vagrant/vagrant/fpm.conf /etc/php/7.2/fpm/pool.d/pixelfed.conf && \
                     systemctl restart php7.2-fpm && \ 
                     cp /vagrant/vagrant/nginx.conf /etc/nginx/sites-available/pixelfed.conf && \
                     rm /etc/nginx/sites-enabled/default && \
                     ln -s /etc/nginx/sites-available/pixelfed.conf /etc/nginx/sites-enabled/ && \
                     systemctl restart nginx && \
                     php artisan key:generate && php artisan storage:link && \
                     php artisan migrate && php artisan horizon && \
                     php artisan serve --host=localhost --port=80
                      "

    end

    config.vm.provider "virtualbox" do |v|
      v.customize ["modifyvm", :id, "--natdnshostresolver1", "on"]
      v.customize ["modifyvm", :id, "--memory", "2048"]
    end

    config.vm.network :forwarded_port, guest: 80, host: 8080
end


