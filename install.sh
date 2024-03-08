#!/bin/bash
set -e

if [ "${BASH_VERSION}" = '' ]; then
    echo "This script must be run in bash"
    exit 1
fi

set -u

# Check for root privileges
if [[ ${EUID} -ne 0 ]]; then
    echo "This script must be run as root"
    exit 1
fi

function configure_nginx() {
    patch --forward --ignore-whitespace /etc/nginx/nginx.conf <<EOF || true
@@ -2,10 +2,13 @@
 worker_processes auto;
 pid /run/nginx.pid;
 include /etc/nginx/modules-enabled/*.conf;
+worker_rlimit_nofile 65535;
+pcre_jit on;

 events {
-             worker_connections 768;
-             # multi_accept on;
+             worker_connections 8192;
+             multi_accept on;
+             use epoll;
 }

 http {
@@ -31,8 +34,8 @@
        # SSL Settings
        ##

-             ssl_protocols TLSv1 TLSv1.1 TLSv1.2 TLSv1.3; # Dropping SSLv3, ref: POODLE
-             ssl_prefer_server_ciphers on;
+             #ssl_protocols TLSv1 TLSv1.1 TLSv1.2 TLSv1.3; # Dropping SSLv3, ref: POODLE
+             #ssl_prefer_server_ciphers on;

        ##
        # Logging Settings
EOF

    cat > /etc/nginx/conf.d/zz-user.conf <<EOF
# Basic
server_tokens off;

# Client
keepalive_requests 10000;
reset_timedout_connection on;
client_body_timeout 10s;
client_header_timeout 10s;
send_timeout 300s;
server_names_hash_bucket_size 64;

# SSL
ssl_protocols TLSv1.3;
ssl_prefer_server_ciphers off;
ssl_session_timeout 1d;
ssl_session_cache shared:SSL:10m;
ssl_early_data on;
resolver 127.0.0.53 ipv6=off;

# Gzip
gzip_vary on;
gzip_proxied any;
gzip_comp_level 6;
gzip_types image/svg+xml text/plain text/css application/json application/javascript text/xml application/xml application/xml+rss text/javascript;
gzip_min_length 512;
gzip_buffers 128 4k;
EOF

    if [[ ! -f /etc/ssl/dhparam.pem ]]; then
      openssl dhparam -out /etc/ssl/dhparam.pem 2048
    fi
}

function configure_nginx_vhost() {
    SERVERNAME='ylilauta-mediaserver'
    CERTSTORE='https://certstore.example.com'
    STORAGE='https://storage.example.com'

    echo "Please enter certstore URL."
    echo "Leave empty to use '${CERTSTORE}'"
    read -r -p "Certstore URL: "
    if [ -n "${REPLY}" ] ; then
        CERTSTORE=${REPLY}
    fi
    echo "Please enter file storage URL."
    echo "Leave empty to use '${STORAGE}'"
    read -r -p "File storage URL: "
    if [ -n "${REPLY}" ] ; then
        STORAGE=${REPLY}
    fi

    cat > /etc/nginx/sites-available/${SERVERNAME} <<EOF
fastcgi_cache_path /srv/www/${SERVERNAME}/cache levels=2:2 keys_zone=files:100m inactive=60m max_size=500G min_free=10G;
fastcgi_cache_key "\$request_uri";

server {
    listen 127.0.0.1:80;
    server_name localhost;

    log_not_found off;
    access_log off;

    location / {
        proxy_pass ${STORAGE};
    }
}

server {
    listen 80 default_server reuseport;
    listen [::]:80 default_server reuseport;
    server_name _;

    log_not_found off;
    root /srv/www/${SERVERNAME}/public;

    location ~ "^/.well-known/acme-challenge/" {
        proxy_pass ${CERTSTORE};
    }
    location ~ "^/[a-z0-9]{16}\$" {
        fastcgi_ignore_client_abort on;
        try_files \$uri /index.php;
    }
    location / {
        return 301 https://\$host\$request_uri;
    }

    location ~ \.php\$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
    }
}

server {
    listen 443 ssl http2 default_server reuseport;
    listen [::]:443 ssl http2 default_server reuseport;
    server_name _;

    log_not_found off;
    access_log off;

    ssl_certificate /etc/ssl/${SERVERNAME}/fullchain.pem;
    ssl_certificate_key /etc/ssl/${SERVERNAME}/privkey.pem;
    ssl_trusted_certificate /etc/ssl/${SERVERNAME}/chain.pem;
    ssl_stapling on;
    ssl_stapling_verify on;

    root /srv/www/${SERVERNAME}/public;
    try_files \$uri /index.php;

    location ~ /\. { return 404; }
    location ~ \.php\$ {
        include snippets/fastcgi-php.conf;
        fastcgi_cache files;
        fastcgi_cache_valid 200 365d;
        fastcgi_cache_valid any 1m;
        fastcgi_cache_lock on;
        fastcgi_cache_background_update on;
        fastcgi_cache_use_stale error timeout updating http_500 http_503;
        fastcgi_ignore_client_abort on;
        add_header 'X-Cache-Status' \$upstream_cache_status always;
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
    }
}
EOF

    rm /etc/nginx/sites-enabled/default
    if [[ -f /etc/nginx/sites-enabled/${SERVERNAME} ]]; then
        rm /etc/nginx/sites-enabled/${SERVERNAME}
    fi

    ln -s /etc/nginx/sites-available/${SERVERNAME} /etc/nginx/sites-enabled/

    service nginx reload

    ssh-keygen -f /root/.ssh/id_rsa -q -N ""

    echo "Add the following key to certstore root ssh authorized_keys:"
    echo
    cat /root/.ssh/id_rsa.pub
    echo
    read -r -p "Press enter to continue..."

    CERTSTOREDOMAIN=`echo ${CERTSTORE} | sed 's/http\(s:\|:\)\/\///'`
    /usr/bin/rsync -avzL -e ssh ${CERTSTOREDOMAIN}:/etc/ssl/ylilauta-mediaserver/* /etc/ssl/${SERVERNAME}
    mkdir -p /srv/www/${SERVERNAME}
    cd /srv/www/${SERVERNAME}
    git clone https://github.com/LautaMedia/Ylilauta-MediaServer.git .
    cp -n /srv/www/${SERVERNAME}/Config.sample /srv/www/${SERVERNAME}/config/Config.php

    chown -R 33:33 /srv/www
    chmod -R 774 /srv/www
    git checkout .
    cat > /etc/cron.d/${SERVERNAME} <<EOF
# Update certs
0 0 * * * root /usr/bin/rsync -avzLq -e ssh ${CERTSTOREDOMAIN}:/etc/ssl/ylilauta-mediaserver/* /etc/ssl/${SERVERNAME}
11 0 * * * root /usr/sbin/service nginx reload
EOF

    echo
    echo "Installation done."
    echo "Please configure the MediaServer in /srv/www/${SERVERNAME}/src/Config/Config.php"
    echo

    read -r -p "Press enter to continue..."
}

function install_php() {
    apt install -y imagemagick webp php8.3-fpm php8.3-gd php8.3-curl php8.3-imagick

    sed -i -e 's/pm = dynamic/pm = static/g' /etc/php/8.3/fpm/pool.d/www.conf
    sed -i -e 's/pm.max_children = 5/pm.max_children = 100/g' /etc/php/8.3/fpm/pool.d/www.conf
    sed -i -e 's/;pm.max_requests = 500/pm.max_requests = 10000/g' /etc/php/8.3/fpm/pool.d/www.conf

    sed -i -e 's/;opcache.enable=1/opcache.enable=1/g' /etc/php/8.3/fpm/php.ini
    sed -i -e 's/;opcache.validate_timestamps=1/opcache.validate_timestamps=0/g' /etc/php/8.3/fpm/php.ini
    sed -i -e 's/;opcache.huge_code_pages=1/opcache.huge_code_pages=1/g' /etc/php/8.3/fpm/php.ini

    service php8.3-fpm restart
}

function install_deps() {
    apt install -y libavif-bin libheif-plugin-aomdec libheif-plugin-aomenc
}

if [[ ! $(which php) ]]; then
    install_php
fi
if [[ ! $(which avifenc) ]]; then
    install_deps
fi

NGINX_WAS_ALREADY_INSTALLED='Y'
if [[ ! $(which nginx) ]]; then
    apt install -y nginx
    NGINX_WAS_ALREADY_INSTALLED='N'
fi

if [[ ${NGINX_WAS_ALREADY_INSTALLED} == 'Y' ]]; then
    echo "Nginx was already installed before."
    read -p "Do you want to reconfigure nginx? (y/n) [n]: " -r
    if [[ ${REPLY} =~ ^[Yy]$ ]] ; then
        configure_nginx
    fi
else
    configure_nginx
fi

configure_nginx_vhost