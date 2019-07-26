FROM php:7.3.6-apache

RUN apt-get update && apt-get install -y \
        vim \
        wget \
        openssl \
        git \
        libicu-dev \
        libc-client-dev \
        libkrb5-dev \
        libxml2-dev \
        libzip-dev \
        zlib1g-dev \
        && apt-get clean \
        && rm -rf /var/lib/apt/lists/*

ENV APCU_VERSION 5.1.17

# Basic lumen packages
RUN docker-php-ext-install \
        intl \
        pdo_mysql \
        xml \
        zip \
    && pecl install \
        apcu-${APCU_VERSION} \
    && docker-php-ext-enable --ini-name 20-apcu.ini apcu \
    && docker-php-ext-enable --ini-name 05-opcache.ini opcache

RUN docker-php-ext-configure imap --with-kerberos --with-imap-ssl \
    && docker-php-ext-install imap

# Add php.ini for production
COPY docker/php/php.ini-production $PHP_INI_DIR/php.ini
COPY docker/php/php.ini $PHP_INI_DIR/conf.d/php.ini

#  Configuring Apache
COPY docker/apache/apache2.conf /etc/apache2/apache2.conf
RUN  rm /etc/apache2/sites-available/000-default.conf \
         && rm /etc/apache2/sites-enabled/000-default.conf


# Enable rewrite module
RUN a2enmod rewrite

COPY docker/app/install-composer.sh /usr/local/bin/docker-app-install-composer
RUN chmod +x /usr/local/bin/docker-app-install-composer

RUN docker-app-install-composer \
    && mv composer.phar /usr/local/bin/composer

# https://getcomposer.org/doc/03-cli.md#composer-allow-superuser
ENV COMPOSER_ALLOW_SUPERUSER 1

RUN composer global require "hirak/prestissimo:^0.3" --prefer-dist --no-progress --no-suggest --optimize-autoloader --classmap-authoritative \
    && composer clear-cache

WORKDIR /var/www/html

COPY composer.json ./
COPY composer.lock ./

RUN mkdir -p \
        var/cache \
        var/log \
        var/sessions \
    && composer install --prefer-dist --no-dev --no-autoloader --no-scripts --no-progress --no-suggest \
    && composer clear-cache \
# Permissions hack because setfacl does not work on Mac and Windows
    && chown -R www-data var \
    && chmod -R ug+rwx var

COPY config config/
COPY bin bin/
COPY src src/
COPY public public/
COPY translations translations/
COPY .env .env

RUN composer dump-autoload --optimize --classmap-authoritative --no-dev

COPY docker/app/docker-entrypoint.sh /usr/local/bin/docker-app-entrypoint
RUN chmod +x /usr/local/bin/docker-app-entrypoint

ENTRYPOINT ["docker-app-entrypoint"]
