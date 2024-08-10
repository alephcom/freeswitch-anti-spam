#!/bin/sh
set -e
echo "Deploying application ..."

chmod +x ../scripts/*.sh

PWD=`pwd`

# Enter maintenance mode
(php artisan down --refresh=15) || true
    # Update codebase
    git fetch origin master
    git reset --hard origin/master
    # Install dependencies based on lock file
    composer install --no-interaction --prefer-dist --optimize-autoloader
    # Migrate database
    php artisan migrate --force --step
    php artisan config:cache
    # Note: If you're using queue workers, this is the place to restart them.
    php artisan queue:restart
    # Clear cache
    #php artisan optimize
    # Reload PHP to update opcache
    #echo "" | sudo -S service php7.4-fpm reload
# Exit maintenance mode
php artisan up
echo "Application deployed!"

