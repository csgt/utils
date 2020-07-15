#!/usr/bin/env bash

set -e

role=${CONTAINER_ROLE:-app}
env=${APP_ENV:-production}

if [ "$role" = "app" ]; then
    exec php-fpm
elif [ "$role" = "horizon" ]; then
    while [ true ]
    do
        php /var/www/artisan horizon
    done

elif [ "$role" = "scheduler" ]; then
    while [ true ]
    do
        php /var/www/artisan schedule:run --verbose --no-interaction
        sleep 60
    done
fi
