FROM php:8.3-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends libicu-dev libzip-dev unzip \
    && docker-php-ext-install intl pdo_mysql zip opcache \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www/html
COPY composer.json composer.lock symfony.lock ./
RUN composer install --no-interaction --prefer-dist --no-progress --no-scripts --no-autoloader
COPY . .
RUN composer dump-autoload --optimize --no-scripts \
    && export APP_RUNTIME_OPTIONS='{"disable_dotenv":true}' \
       APP_ENV=dev APP_SECRET=build DEFAULT_URI=http://localhost:8000 \
       DATABASE_URL='mysql://build:build@localhost/app?serverVersion=8.0.32' \
       MAILER_DSN=null://null MESSENGER_TRANSPORT_DSN=doctrine://default \
    && php bin/console assets:install public --no-interaction \
    && php bin/console importmap:install --no-interaction \
    && rm -rf var/cache \
    && mkdir -p var public/uploads \
    && chown -R www-data:www-data var public/uploads
