#!/usr/bin/env bash
PHPVER=8.1

# Don't bring up questions to stall the script
export DEBIAN_FRONTEND=noninteractive

# Set locales
update-locale LANG=en_US.UTF-8 LANGUAGE=en_US.UTF-8 LC_ALL=en_US.UTF-8

apt update
apt install -y nginx
apt install -y imagemagick webp libavif-bin
apt install -y php${PHPVER}-fpm php${PHPVER}-gd php${PHPVER}-curl php${PHPVER}-imagick

# Dev only requirements
apt install -y php${PHPVER}-dom php${PHPVER}-mbstring

# Config
{
  echo "[PHP]";
  echo "error_reporting = E_ALL";
  echo "display_errors = On";
  echo "display_startup_errors = On";
} >> /etc/php/${PHPVER}/fpm/conf.d/99-user.ini

# Sendfile messes up with caches
sed -i -e 's/\t\?sendfile on;/\tsendfile off;/g' /etc/nginx/nginx.conf

cat > /etc/nginx/conf.d/zz-user.conf << EOM
# Basic
fastcgi_buffers 32 4k;
fastcgi_buffer_size 32k;

client_max_body_size 1G;

# Gzip
gzip_vary on;
gzip_proxied any;
gzip_comp_level 6;
gzip_types image/svg+xml text/plain text/css application/json application/javascript text/xml application/xml application/xml+rss text/javascript;
gzip_min_length 512;
EOM

# Nginx vhosts
rm /etc/nginx/sites-available/*
rm /etc/nginx/sites-enabled/*
cat > /etc/nginx/sites-available/default << EOM
#fastcgi_cache_path /vagrant/cache levels=2:2 keys_zone=files:1m inactive=1d max_size=1G;
#fastcgi_cache_key "\$request_uri";

server {
    server_name _;
    listen 8002 default_server;
    listen [::]:8002 default_server;
    root /vagrant/public;

    index index.php;
    try_files \$uri /index.php;

    if (\$query_string != '') { return 404; }

    location ~ \.php\$ {
        include snippets/fastcgi-php.conf;
        #fastcgi_cache files;
        #fastcgi_cache_valid 200 365d;
        #fastcgi_cache_valid any 5s;
        #fastcgi_cache_lock on;
        #fastcgi_cache_background_update on;
        #fastcgi_cache_use_stale error timeout updating http_500 http_503;
        #fastcgi_ignore_client_abort on;
        #add_header 'X-Cache-Status' \$upstream_cache_status always;
        fastcgi_pass unix:/var/run/php/php${PHPVER}-fpm.sock;
    }
}
EOM
ln -s /etc/nginx/sites-available/default /etc/nginx/sites-enabled/default
mkdir -p /vagrant/config
cp -n /vagrant/Config.sample /vagrant/config/Config.php

# Install composer
apt install unzip -y
cd /tmp || exit
curl -s https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer

# Install dependencies
cd /vagrant || exit
composer install

service nginx restart
service php${PHPVER}-fpm restart