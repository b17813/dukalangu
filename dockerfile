FROM php:8.2-apache

# Sakinisha mazingira ya MySQL kwa ajili ya PHP
RUN apt-get update && apt-get install -y \
    libpng-dev \
    zip \
    unzip \
    && docker-php-ext-install mysqli pdo pdo_mysql

# Washa Apache mod_rewrite (muhimu kwa mifumo mingi ya PHP)
RUN a2enmod rewrite

# Nakili kodi zako zote kuingia kwenye container
COPY . /var/www/html/

# Weka ruhusa (permissions) sahihi
RUN chown -R www-data:www-data /var/www/html && chmod -R 755 /var/www/html

EXPOSE 80