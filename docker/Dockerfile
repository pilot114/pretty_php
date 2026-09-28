# Development image: the latest official PHP with the extensions Pretty PHP needs and Composer
FROM php:latest

# ext-sockets is required by composer.json and missing from the official image;
# git and unzip let Composer install packages from sources and dist archives
RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip \
    && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-install sockets

COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

# The container runs with the host UID, which has no home directory in the image
ENV COMPOSER_HOME=/tmp/composer

WORKDIR /app
