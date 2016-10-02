FROM php:7-apache

RUN apt update \
    && apt install -y \
        php5-sqlite \
        php5-xdebug \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

RUN pecl install xdebug && docker-php-ext-enable xdebug

RUN echo "xdebug.remote_autostart=1" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini