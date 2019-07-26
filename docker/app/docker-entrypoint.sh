#!/bin/sh
set -e

if [ "$APP_ENV" = 'prod' ]; then
	composer install --prefer-dist --no-dev --no-progress --no-suggest --optimize-autoloader --classmap-authoritative --no-interaction
else
	composer install --prefer-dist --no-progress --no-suggest --no-interaction
fi

php bin/console assets:install

# Permissions hack because setfacl does not work on Mac and Windows
chown -R www-data var

echo "" > .env

docker-php-entrypoint
apache2-foreground

#exec "$@"
